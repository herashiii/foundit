<?php
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

function h($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function ensureDir(string $path): void {
  if (!is_dir($path)) mkdir($path, 0775, true);
}

$pdo = db();

// Dropdown data  
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$locations  = $pdo->query("SELECT id, name FROM locations WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
$offices    = $pdo->query("SELECT id, name FROM offices WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

// Find IDs category id
$idCategoryId = null;
foreach ($categories as $c) {
  if (mb_strtolower($c['name']) === 'ids') {
    $idCategoryId = (int)$c['id'];
    break;
  }
}

$errors = [];
$postedStep = (int)($_POST['current_step'] ?? 1);

// Defaults / “old values”
$old = $_POST ?? [];
$oldCategoryId = (int)($old['category_id'] ?? 0);
$oldCustody = $old['custody_state'] ?? 'with_finder';

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $category_id = (int)($old['category_id'] ?? 0);
  $title = trim($old['title'] ?? '');
  $description = trim($old['description'] ?? '');

  $found_location_id = (int)($old['found_location_id'] ?? 0);
  $found_at_detail = trim($old['found_at_detail'] ?? '');
  $found_date = trim($old['found_date'] ?? '');
  $found_time = trim($old['found_time'] ?? '');

  $custody_state = trim($old['custody_state'] ?? 'with_finder');
  $office_id = (int)($old['office_id'] ?? 0);

  $reporter_email = trim($old['reporter_email'] ?? '');
  $reporter_phone = trim($old['reporter_phone'] ?? '');
  $visibility = trim($old['reporter_visibility'] ?? 'anonymous_to_owner');

  // ID-only
  $id_type = trim($old['id_type'] ?? '');
  $name_on_id = trim($old['name_on_id'] ?? '');
  $department = trim($old['department'] ?? '');
  $distinct_feature = trim($old['distinct_feature'] ?? '');

  // Files
$files = $_FILES['photos'] ?? null;

// Debug logging (remove after testing)
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

// Photos required: at least 1, up to 5
$photoCount = 0;
$uploadedFiles = [];

if ($files && isset($files['name']) && is_array($files['name'])) {
    foreach ($files['name'] as $index => $name) {
        if ($name !== '') {
            // Check if this file was actually uploaded successfully
            if ($files['error'][$index] === UPLOAD_ERR_OK) {
                $photoCount++;
                $uploadedFiles[] = [
                    'name' => $name,
                    'tmp_name' => $files['tmp_name'][$index],
                    'error' => $files['error'][$index],
                    'size' => $files['size'][$index]
                ];
                error_log("Valid file: $name");
            } else {
                // File upload error
                $errorCode = $files['error'][$index];
                error_log("File upload error for $name: $errorCode");
                
                if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
                    $errors[] = "File '$name' is too large. Maximum size is " . ini_get('upload_max_filesize');
                } else if ($errorCode === UPLOAD_ERR_PARTIAL) {
                    $errors[] = "File '$name' was only partially uploaded.";
                } else if ($errorCode === UPLOAD_ERR_NO_FILE) {
                    // Skip - no file
                } else {
                    $errors[] = "Error uploading file '$name'. Error code: $errorCode";
                }
            }
        }
    }
}

error_log("Total valid files: $photoCount");

if ($photoCount < 1) $errors[] = "Please upload at least 1 photo.";
if ($photoCount > 5) $errors[] = "You can upload up to 5 photos only.";

  // If IDs, require extra
  if ($idCategoryId !== null && $category_id === $idCategoryId) {
    if ($id_type === '') $errors[] = "Please select the type of ID.";
    if ($name_on_id === '') $errors[] = "Please enter the name visible on the ID.";
  }

  if (empty($errors)) {
    try {
      $pdo->beginTransaction();

      // Reporter user: find or create by email (until login)
      $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
      $stmt->execute([':email' => $reporter_email]);
      $user = $stmt->fetch();

      if ($user) {
        $reporter_user_id = (int)$user['id'];
        $pdo->prepare("UPDATE users SET phone = COALESCE(phone, :phone) WHERE id = :id")
            ->execute([':phone' => $reporter_phone, ':id' => $reporter_user_id]);
      } else {
        // Extract name from email (part before @)
        $emailName = strstr($reporter_email, '@', true) ?: 'Reporter';
        
        // Split into first and last name (simple approach)
        $nameParts = explode(' ', $emailName, 2);
        $first_name = $nameParts[0];
        $last_name = isset($nameParts[1]) ? $nameParts[1] : '';
        
        $pdo->prepare("
          INSERT INTO users (role, first_name, last_name, email, phone, is_active)
          VALUES ('student', :first_name, :last_name, :email, :phone, 1)
        ")->execute([
          ':first_name' => $first_name,
          ':last_name' => $last_name,
          ':email' => $reporter_email,
          ':phone' => $reporter_phone
        ]);
        $reporter_user_id = (int)$pdo->lastInsertId();
      }

      // Insert item
$pdo->prepare("
  INSERT INTO items (
    category_id, status, title, description,
    found_location_id, found_at_detail,
    found_date, found_time,
    custody_state, office_id,
    user_id, reporter_visibility
  ) VALUES (
    :category_id, 'recent', :title, :description,
    :found_location_id, :found_at_detail,
    :found_date, :found_time,
    :custody_state, :office_id,
    :user_id, :reporter_visibility
  )
")->execute([
  ':category_id' => $category_id,
  ':title' => $title,
  ':description' => $description !== '' ? $description : null,
  ':found_location_id' => $found_location_id,
  ':found_at_detail' => $found_at_detail !== '' ? $found_at_detail : null,
  ':found_date' => $found_date,
  ':found_time' => $found_time !== '' ? $found_time : null,
  ':custody_state' => $custody_state,
  ':office_id' => ($custody_state === 'at_office') ? $office_id : null,
  ':user_id' => $reporter_user_id,
  ':reporter_visibility' => $visibility
]);

      $item_id = (int)$pdo->lastInsertId();

      // ID details table (only if you created it)
      if ($idCategoryId !== null && $category_id === $idCategoryId) {
        $pdo->exec("
          CREATE TABLE IF NOT EXISTS item_id_details (
            item_id INT UNSIGNED PRIMARY KEY,
            id_type VARCHAR(40) NOT NULL,
            name_on_id VARCHAR(140) NOT NULL,
            department VARCHAR(140) NULL,
            distinct_feature VARCHAR(160) NULL,
            CONSTRAINT fk_iddetails_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
          ) ENGINE=InnoDB
        ");

        $pdo->prepare("
          INSERT INTO item_id_details (item_id, id_type, name_on_id, department, distinct_feature)
          VALUES (:item_id, :id_type, :name_on_id, :department, :distinct_feature)
        ")->execute([
          ':item_id' => $item_id,
          ':id_type' => $id_type,
          ':name_on_id' => $name_on_id,
          ':department' => $department !== '' ? $department : null,
          ':distinct_feature' => $distinct_feature !== '' ? $distinct_feature : null
        ]);
      }

      // Upload photos
      $baseUploadDir = __DIR__ . "/uploads/items/{$item_id}";
      ensureDir($baseUploadDir);

      $insertPhoto = $pdo->prepare("
        INSERT INTO item_photos (item_id, file_path, sort_order)
        VALUES (:item_id, :file_path, :sort_order)
      ");

      $allowedExt = ['jpg','jpeg','png','webp'];
      $sortOrder = 1;

      foreach ($uploadedFiles as $file) {
        $tmp = $file['tmp_name'];
        $origName = $file['name'];
        
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) throw new RuntimeException("Only JPG, PNG, or WEBP allowed.");

        $safeName = "photo_" . $sortOrder . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $destFs = $baseUploadDir . "/" . $safeName;

        if (!move_uploaded_file($tmp, $destFs)) throw new RuntimeException("Could not save uploaded photo.");

$webPath = "uploads/items/{$item_id}/{$safeName}";

        $insertPhoto->execute([
          ':item_id' => $item_id,
          ':file_path' => $webPath,
          ':sort_order' => $sortOrder
        ]);

        $sortOrder++;
        if ($sortOrder > 5) break;
      }

      $pdo->commit();
      header("Location: view-item.php?id={$item_id}&submitted=1");
      exit;

    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $errors[] = "Save failed: " . $e->getMessage();
      // Keep user on the step they were on
    }
  }
}
?>

<main class="report-shell">
  <div class="report-container">

    <header class="report-header">
      <h1>Turn In a Found Item</h1>
      <p>Thank you for helping return a lost belonging. Follow the steps and we’ll help locate the owner safely.</p>
    </header>

    <?php if (!empty($errors)): ?>
      <div class="form-alert" role="alert">
        <strong>Please fix the following:</strong>
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= h($e) ?></li>
          <?php endforeach; ?>
        </ul>
        <p class="alert-note">Your entered information is kept. Continue where you left off.</p>
      </div>
    <?php endif; ?>

    <div class="report-stepper" aria-label="Progress">
      <div class="step" data-step="1">Photos</div>
      <div class="step" data-step="2">Item</div>
      <div class="step" data-step="3">Location</div>
      <div class="step" data-step="4">Custody</div>
      <div class="step" data-step="5">Finish</div>
    </div>

    <form id="reportForm" method="post" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="current_step" id="currentStepInput" value="<?= (int)$postedStep ?>">
      <input type="hidden" name="MAX_FILE_SIZE" value="20971520"> <!-- 20MB max file size -->

      <!-- STEP 1 -->
      <section class="report-card" data-step="1">
        <h2>Upload Photos</h2>
        <p class="helper">Upload 1–5 photos. Clear photos help the owner recognize it faster.</p>

        <div class="upload-zone" id="dropZone">
          <input type="file" id="photoInput" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
          <div class="zone-content">
            <span class="zone-icon">📷</span>
            <p><strong>Click to upload</strong> or drag and drop</p>
            <span class="upload-hint">JPG, PNG, WEBP (Max 5 photos, 20MB each)</span>
          </div>
        </div>

        <div class="step-error" id="step1Error" aria-live="polite"></div>
        <div id="previewContainer" class="preview-container"></div>

        <div class="nav-actions">
          <button type="button" class="btn-primary nextBtn">Continue</button>
        </div>
      </section>

      <!-- STEP 2 -->
      <section class="report-card" data-step="2">
        <h2>Identify the Item</h2>

        <label>Category <span class="req">*</span></label>
        <div class="category-grid" id="categoryGrid">
          <?php foreach ($categories as $cat): ?>
            <button type="button" class="cat" data-id="<?= (int)$cat['id'] ?>" data-name="<?= h($cat['name']) ?>">
              <?= h($cat['name']) ?>
            </button>
          <?php endforeach; ?>
        </div>
        <input type="hidden" name="category_id" id="categoryId" value="<?= (int)$oldCategoryId ?>">

        <!-- ID-only -->
        <div class="id-extra" id="idExtra" <?= ($idCategoryId !== null && $oldCategoryId === $idCategoryId) ? '' : 'hidden' ?>>
          <div class="id-extra-head">
            <strong>ID Card Details</strong>
            <span class="muted">Provide recognition details only.</span>
          </div>

          <label>Type of ID <span class="req">*</span></label>
          <select name="id_type" id="idType">
            <option value="">Select one</option>
            <?php
              $idTypes = [
                "School ID","Driver’s License","National ID (PhilSys)","Passport","Postal ID","PRC ID","Voter’s ID","Company ID","Other"
              ];
              foreach ($idTypes as $t):
            ?>
              <option value="<?= h($t) ?>" <?= (($old['id_type'] ?? '') === $t) ? 'selected' : '' ?>><?= h($t) ?></option>
            <?php endforeach; ?>
          </select>

          <label>Name visible on the ID <span class="req">*</span></label>
          <input type="text" name="name_on_id" id="nameOnId" value="<?= h($old['name_on_id'] ?? '') ?>" placeholder="Enter exactly as printed">

          <label>Department / College (optional)</label>
          <input type="text" name="department" value="<?= h($old['department'] ?? '') ?>" placeholder="e.g., CCS, Nursing, Arts & Sciences">

          <label>Distinct feature (optional)</label>
          <input type="text" name="distinct_feature" value="<?= h($old['distinct_feature'] ?? '') ?>" placeholder="e.g., blue lanyard, sticker, cracked holder">
        </div>

        <label>Short Title <span class="req">*</span></label>
        <input type="text" name="title" id="titleInput" value="<?= h($old['title'] ?? '') ?>" placeholder="e.g., Black wallet, School ID with lanyard">

        <label>Description (optional)</label>
        <textarea name="description" class="desc" rows="4" placeholder="Add helpful recognition details (avoid sensitive info)."><?= h($old['description'] ?? '') ?></textarea>

        <div class="step-error" id="step2Error" aria-live="polite"></div>

        <div class="nav-actions">
          <button type="button" class="btn-secondary prevBtn">Back</button>
          <button type="button" class="btn-primary nextBtn">Continue</button>
        </div>
      </section>

      <!-- STEP 3 -->
      <section class="report-card" data-step="3">
        <h2>Where did you find it?</h2>

        <label>Main Location <span class="req">*</span></label>
        <select name="found_location_id" id="foundLocation">
          <option value="">Select location</option>
          <?php foreach ($locations as $loc): ?>
            <option value="<?= (int)$loc['id'] ?>" <?= ((int)($old['found_location_id'] ?? 0) === (int)$loc['id']) ? 'selected' : '' ?>>
              <?= h($loc['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label>Exact Spot (optional)</label>
        <input type="text" name="found_at_detail" value="<?= h($old['found_at_detail'] ?? '') ?>" placeholder="e.g., near stairs, table by entrance">

        <label>Date Found <span class="req">*</span></label>
        <input type="date" name="found_date" id="foundDate"
               value="<?= h(($old['found_date'] ?? date('Y-m-d'))) ?>">

        <label>Time Found (optional)</label>
        <input type="time" name="found_time" value="<?= h($old['found_time'] ?? '') ?>">

        <div class="step-error" id="step3Error" aria-live="polite"></div>

        <div class="nav-actions">
          <button type="button" class="btn-secondary prevBtn">Back</button>
          <button type="button" class="btn-primary nextBtn">Continue</button>
        </div>
      </section>

      <!-- STEP 4 -->
      <section class="report-card" data-step="4">
        <h2>Where is the item now?</h2>

        <div class="radio-group">
          <label class="radio">
            <input type="radio" name="custody_state" value="with_finder" <?= ($oldCustody === 'with_finder') ? 'checked' : '' ?>>
            <span>I still have the item</span>
          </label>

          <label class="radio">
            <input type="radio" name="custody_state" value="at_office" <?= ($oldCustody === 'at_office') ? 'checked' : '' ?>>
            <span>I left it at a campus office</span>
          </label>
        </div>

        <div id="officeWrap" class="office-wrap" <?= ($oldCustody === 'at_office') ? '' : 'hidden' ?>>
          <label>Office holding the item <span class="req">*</span></label>
          <select name="office_id" id="officeSelect">
            <option value="">Select office</option>
            <?php foreach ($offices as $o): ?>
              <option value="<?= (int)$o['id'] ?>" <?= ((int)($old['office_id'] ?? 0) === (int)$o['id']) ? 'selected' : '' ?>>
                <?= h($o['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="step-error" id="step4Error" aria-live="polite"></div>

        <div class="nav-actions">
          <button type="button" class="btn-secondary prevBtn">Back</button>
          <button type="button" class="btn-primary nextBtn">Continue</button>
        </div>
      </section>

      <!-- STEP 5 -->
      <section class="report-card" data-step="5">
        <h2>Contact & Privacy</h2>

        <label>Email <span class="req">*</span></label>
        <input type="email" name="reporter_email" id="emailInput" value="<?= h($old['reporter_email'] ?? '') ?>" placeholder="you@su.edu.ph">

        <label>Phone Number <span class="req">*</span></label>
        <input type="text" name="reporter_phone" id="phoneInput" value="<?= h($old['reporter_phone'] ?? '') ?>" placeholder="09XX XXX XXXX">

        <label>Owner Contact Preference <span class="req">*</span></label>
        <select name="reporter_visibility" id="visSelect">
          <option value="share_with_owner" <?= (($old['reporter_visibility'] ?? '') === 'share_with_owner') ? 'selected' : '' ?>>Owner may contact me</option>
          <option value="anonymous_to_owner" <?= (($old['reporter_visibility'] ?? 'anonymous_to_owner') === 'anonymous_to_owner') ? 'selected' : '' ?>>Keep my identity private</option>
        </select>

        <div class="step-error" id="step5Error" aria-live="polite"></div>

        <div class="nav-actions">
          <button type="button" class="btn-secondary prevBtn">Back</button>
          <button type="submit" class="btn-primary">Submit Report</button>
        </div>
      </section>

    </form>
  </div>
</main>

<script>
  // Simple stepper
  let currentStep = parseInt(document.getElementById('currentStepInput').value) || 1;
  
  function showStep(step) {
    currentStep = step;
    document.getElementById('currentStepInput').value = step;
    
    document.querySelectorAll('.report-card').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    
    document.querySelector(`.report-card[data-step="${step}"]`).classList.add('active');
    document.querySelector(`.step[data-step="${step}"]`).classList.add('active');
    
    window.scrollTo({ top: 0 });
  }
  
  showStep(currentStep);

  // Photo upload - IMPROVED VERSION
  const photoInput = document.getElementById('photoInput');
  const dropZone = document.getElementById('dropZone');
  const preview = document.getElementById('previewContainer');
  const step1Error = document.getElementById('step1Error');
  
  // Store files in an array to persist them
  let selectedFiles = [];

  // Style the drop zone
  dropZone.style.position = 'relative';
  dropZone.style.cursor = 'pointer';
  
  // Make file input invisible but functional
  photoInput.style.position = 'absolute';
  photoInput.style.top = '0';
  photoInput.style.left = '0';
  photoInput.style.width = '100%';
  photoInput.style.height = '100%';
  photoInput.style.opacity = '0';
  photoInput.style.cursor = 'pointer';
  photoInput.style.zIndex = '10';

  // Click handler
  dropZone.addEventListener('click', function(e) {
    if (e.target !== photoInput) {
      photoInput.click();
    }
  });

  // Prevent click on photoInput from bubbling
  photoInput.addEventListener('click', function(e) {
    e.stopPropagation();
  });

  // Handle file selection
  photoInput.addEventListener('change', function(e) {
    const files = Array.from(this.files);
    console.log('Files selected:', files.length);
    
    // Add new files to our persistent array
    files.forEach(file => {
      if (selectedFiles.length < 5) {
        if (file.type.startsWith('image/')) {
          selectedFiles.push(file);
        }
      }
    });
    
    // Update the file input with all selected files
    updateFileInput();
    
    // Show previews
    renderPreviews();
    
    // Clear any errors
    step1Error.style.display = 'none';
    dropZone.classList.remove('input-error');
  });

  // Update the file input with our persistent file array
  function updateFileInput() {
    const dt = new DataTransfer();
    selectedFiles.forEach(file => {
      dt.items.add(file);
    });
    photoInput.files = dt.files;
    console.log('File input updated, now has:', photoInput.files.length, 'files');
  }

  // Render previews from selectedFiles array
  function renderPreviews() {
    preview.innerHTML = '';
    
    selectedFiles.forEach((file, index) => {
      const reader = new FileReader();
      
      reader.onload = function(e) {
        const previewDiv = document.createElement('div');
        previewDiv.className = 'preview-tile';
        previewDiv.style.position = 'relative';
        previewDiv.style.display = 'inline-block';
        previewDiv.style.margin = '10px';
        
        const img = document.createElement('img');
        img.src = e.target.result;
        img.style.width = '100px';
        img.style.height = '100px';
        img.style.objectFit = 'cover';
        img.style.borderRadius = '4px';
        
        // Add remove button
        const removeBtn = document.createElement('button');
        removeBtn.innerHTML = '×';
        removeBtn.style.position = 'absolute';
        removeBtn.style.top = '-5px';
        removeBtn.style.right = '-5px';
        removeBtn.style.width = '20px';
        removeBtn.style.height = '20px';
        removeBtn.style.borderRadius = '50%';
        removeBtn.style.background = '#C53030';
        removeBtn.style.color = 'white';
        removeBtn.style.border = 'none';
        removeBtn.style.cursor = 'pointer';
        removeBtn.style.fontWeight = 'bold';
        removeBtn.style.display = 'flex';
        removeBtn.style.alignItems = 'center';
        removeBtn.style.justifyContent = 'center';
        
        removeBtn.onclick = function(e) {
          e.stopPropagation();
          // Remove file from array
          selectedFiles.splice(index, 1);
          // Update file input
          updateFileInput();
          // Re-render previews
          renderPreviews();
        };
        
        previewDiv.appendChild(img);
        previewDiv.appendChild(removeBtn);
        preview.appendChild(previewDiv);
      };
      
      reader.readAsDataURL(file);
    });
  }

  // Drag and drop
  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
  });

  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  ['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => {
      dropZone.classList.add('dragover');
    });
  });

  ['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => {
      dropZone.classList.remove('dragover');
    });
  });

  dropZone.addEventListener('drop', function(e) {
    const dt = e.dataTransfer;
    const files = Array.from(dt.files);
    
    // Add dropped files to our persistent array
    files.forEach(file => {
      if (selectedFiles.length < 5) {
        if (file.type.startsWith('image/')) {
          selectedFiles.push(file);
        }
      }
    });
    
    // Update file input and previews
    updateFileInput();
    renderPreviews();
  });

  // Step navigation
  document.querySelectorAll('.nextBtn').forEach(btn => {
    btn.addEventListener('click', () => {
      if (currentStep === 1) {
        if (selectedFiles.length === 0) {
          step1Error.textContent = 'Please upload at least 1 photo';
          step1Error.style.display = 'block';
          dropZone.classList.add('input-error');
          return;
        } else {
          console.log('Moving to next step with', selectedFiles.length, 'files');
        }
      }
      
      if (currentStep < 5) {
        showStep(currentStep + 1);
      }
    });
  });

  document.querySelectorAll('.prevBtn').forEach(btn => {
    btn.addEventListener('click', () => {
      if (currentStep > 1) showStep(currentStep - 1);
    });
  });

  // Form submit - ensure files are in the input
  document.getElementById('reportForm').addEventListener('submit', function(e) {
    // Make sure file input has all files before submitting
    updateFileInput();
    
    console.log('Submitting with', photoInput.files.length, 'files');
    
    if (selectedFiles.length === 0) {
      e.preventDefault();
      alert('Please upload at least 1 photo');
      showStep(1);
    }
  });

  // Clickable steps for going back
  document.querySelectorAll('.step').forEach(step => {
    step.addEventListener('click', () => {
      const targetStep = parseInt(step.dataset.step);
      if (targetStep < currentStep) {
        showStep(targetStep);
      }
    });
  });

  // Category selection
  document.addEventListener('DOMContentLoaded', function() {
    const categoryGrid = document.getElementById('categoryGrid');
    const categoryId = document.getElementById('categoryId');
    const idExtra = document.getElementById('idExtra');
    
    if (categoryGrid) {
      console.log('Category grid found, categories:', document.querySelectorAll('.cat').length);
      
      document.querySelectorAll('.cat').forEach(cat => {
        cat.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          
          const catId = this.dataset.id;
          const catName = this.dataset.name;
          
          console.log('Category clicked:', catName, 'ID:', catId);
          
          document.querySelectorAll('.cat').forEach(c => {
            c.classList.remove('active');
          });
          
          this.classList.add('active');
          categoryId.value = catId;
          
          if (catName.toLowerCase() === 'ids') {
            idExtra.hidden = false;
          } else {
            idExtra.hidden = true;
          }
        });
      });
      
      const savedCategoryId = categoryId.value;
      if (savedCategoryId) {
        const selectedCat = document.querySelector(`.cat[data-id="${savedCategoryId}"]`);
        if (selectedCat) {
          selectedCat.classList.add('active');
          if (selectedCat.dataset.name.toLowerCase() === 'ids') {
            idExtra.hidden = false;
          }
        }
      }
    }
  });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>