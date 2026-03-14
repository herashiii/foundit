
<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Store the current page to redirect back after login
    $_SESSION['redirect_after_login'] = 'turn-in-item.php';
    header('Location: ../Login/login.php?error=' . urlencode('Please login first to turn in an item'));
    exit;
}

require_once __DIR__ . '/../includes/db.php';

function h($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function ensureDir(string $path): void {
  if (!is_dir($path)) mkdir($path, 0775, true);
}

$pdo = db();
$currentUserId = $_SESSION['user_id'];

// 1. Fetch Logged-in User Data for the Finish section
$userStmt = $pdo->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
$userStmt->execute([$currentUserId]);
$loggedInUser = $userStmt->fetch();
$userFullName = $loggedInUser['first_name'] . ' ' . $loggedInUser['last_name'];
$userEmail = $loggedInUser['email'];
$userPhone = $loggedInUser['phone'] ?? null;

// 2. Fetch data for Categories and Locations
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$locations  = $pdo->query("SELECT id, name FROM locations WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

// 3. Fetch Active Offices
$offices = $pdo->query("SELECT id, name FROM offices WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

// 4. Find IDs category id
$idCategoryId = null;
foreach ($categories as $c) {
  if (mb_strtolower($c['name']) === 'ids' || mb_strtolower($c['name']) === 'identification cards') {
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

// REDIRECT FLAG - initialize to null
$redirectUrl = null;

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

// Server-side validation checks
if ($category_id === 0) $errors[] = "Please select a category.";
if ($title === '') $errors[] = "Please provide an item title.";
if ($found_location_id === 0) $errors[] = "Please select the location where the item was found.";
if ($found_date === '') $errors[] = "Please provide the date the item was found.";
if ($custody_state === 'at_office' && $office_id === 0) $errors[] = "Please select the office where you left the item.";

// If IDs, require extra
if ($idCategoryId !== null && $category_id === $idCategoryId) {
    if ($id_type === '') $errors[] = "Please select the type of ID.";
    if ($name_on_id === '') $errors[] = "Please enter the name visible on the ID.";
  }

  if (empty($errors)) {
    try {
      $pdo->beginTransaction();

      // Reporter user: find or create by email
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
            user_id, reporter_visibility,
            contact_email, contact_phone
          ) VALUES (
            :category_id, :status, :title, :description,
            :found_location_id, :found_at_detail,
            :found_date, :found_time,
            :custody_state, :office_id,
            :user_id, :reporter_visibility,
            :contact_email, :contact_phone
          )
      ")->execute([
        ':category_id' => $category_id,
        ':status' => 'unclaimed', 
        ':title' => $title,
        ':description' => $description !== '' ? $description : null,
        ':found_location_id' => $found_location_id,
        ':found_at_detail' => $found_at_detail !== '' ? $found_at_detail : null,
        ':found_date' => $found_date,
        ':found_time' => $found_time !== '' ? $found_time : null,
        ':custody_state' => $custody_state,
        ':office_id' => ($custody_state === 'at_office') ? $office_id : null,
        ':user_id' => $currentUserId,
        ':reporter_visibility' => $visibility,
        ':contact_email' => $userEmail,
        ':contact_phone' => $userPhone
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
      
      // SET REDIRECT URL INSTEAD OF DIRECT HEADER
      $redirectUrl = "view-item.php?id={$item_id}&submitted=1";

    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $errors[] = "Save failed: " . $e->getMessage();
    }
  }
}

// CHECK IF WE NEED TO REDIRECT - BEFORE including header
if ($redirectUrl) {
    header("Location: " . $redirectUrl);
    exit;
}

// NOW include header AFTER all processing
include __DIR__ . '/../includes/header.php';
?>

<main class="report-shell">
  <div class="report-container">

    <header class="report-header">
      <h1>Turn In a Found Item</h1>
      <p>Thank you for helping return a lost belonging. Follow the steps and we’ll help locate the owner safely.</p>
    </header>

    <?php if (!empty($errors)): ?>
      <div class="form-alert" role="alert" aria-live="assertive">
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

        <div class="step-error" id="step1Error" aria-live="assertive"></div>
        <div id="previewContainer" class="preview-container"></div>

        <div class="step-actions">
          <button type="button" class="report-btn-primary nextBtn">Continue</button>
        </div>
      </section>

      <section class="report-card" data-step="2" hidden>
        <h2>Identify the Item</h2>
        <p class="helper" style="margin-bottom: 15px; color: #6b7280;">Select a category and provide a brief description.</p>
        
        <label>Category <span class="req">*</span></label>
        <input type="hidden" name="category_id" id="categoryId" value="<?= h($oldCategoryId) ?>" required>
        
        <div class="category-grid" id="categoryGrid">
          <?php foreach ($categories as $cat): ?>
            <div class="cat <?= ($oldCategoryId === (int)$cat['id']) ? 'active' : '' ?>" 
                 data-id="<?= (int)$cat['id'] ?>" 
                 data-name="<?= h($cat['name']) ?>">
              <?= h($cat['name']) ?>
            </div>
          <?php endforeach; ?>
        </div>

        <div id="idExtra" class="id-extra" <?= ($oldCategoryId === $idCategoryId) ? '' : 'hidden' ?>>
          <div class="id-extra-head">
            <strong>ID Details</strong>
            <span class="muted">Required for Identification Cards</span>
          </div>
          
          <label>Type of ID <span class="req">*</span></label>
          <input type="text" name="id_type" placeholder="e.g., Student ID, Driver's License" value="<?= h($old['id_type'] ?? '') ?>">
          
          <label>Name on ID <span class="req">*</span></label>
          <input type="text" name="name_on_id" placeholder="Full name as it appears on the ID" value="<?= h($old['name_on_id'] ?? '') ?>">
          
          <label>Department / Course (optional)</label>
          <input type="text" name="department" placeholder="e.g., College of Nursing" value="<?= h($old['department'] ?? '') ?>">
          
          <label>Distinct Feature (optional)</label>
          <input type="text" name="distinct_feature" placeholder="e.g., with blue lanyard, cracked case" value="<?= h($old['distinct_feature'] ?? '') ?>">
        </div>

        <label for="title">Item Title <span class="req">*</span></label>
        <input type="text" name="title" id="title" placeholder="e.g., Blue Hydro Flask, Black Leather Wallet" value="<?= h($old['title'] ?? '') ?>" required>

        <label for="description">Additional Description (optional)</label>
        <textarea name="description" id="description" rows="3" placeholder="Any distinct features? Brand? Color?"><?= h($old['description'] ?? '') ?></textarea>

        <div class="step-error" id="step2Error" aria-live="assertive"></div>

        <div class="step-actions">
          <button type="button" class="report-btn-secondary prevBtn">Back</button>
          <button type="button" class="report-btn-primary nextBtn">Continue</button>
        </div>
      </section>

      <section class="report-card" data-step="3" hidden>
        <h2>Where did you find it?</h2>
        <p class="helper" style="margin-bottom: 15px; color: #6b7280;">Provide the location and date details.</p>
          
        <label>Campus Area / Building <span class="req">*</span></label>
        <select name="found_location_id" required>
          <option value="">Select location</option>
          <?php foreach ($locations as $loc): ?>
            <option value="<?= (int)$loc['id'] ?>"><?= h($loc['name']) ?></option>
          <?php endforeach; ?>
        </select>

        <label>Specific details (optional)</label>
        <input type="text" name="found_at_detail" placeholder="e.g., Room 204, near the entrance">

        <label>Date Found <span class="req">*</span></label>
        <input type="date" name="found_date" required>

        <label>Time Found (optional)</label>
        <input type="time" name="found_time">

        <div class="step-error" id="step3Error" aria-live="assertive"></div>

        <div class="step-actions">
          <button type="button" class="report-btn-secondary prevBtn">Back</button>
          <button type="button" class="report-btn-primary nextBtn">Continue</button>
        </div>
      </section>

      <section class="report-card" data-step="4" hidden>
        <h2>Custody</h2>
        <p class="helper" style="margin-bottom: 15px; color: #6b7280;">Where can the owner find the item?</p>

        <div class="radio-group">
          <label class="radio">
            <input type="radio" name="custody_state" value="with_finder" checked>
            <div class="radio-content">
              <span style="display: block;">I still have it</span>
              <span style="font-size: 0.85rem; color: #6b7280; font-weight: normal;">The owner will contact you directly to claim it.</span>
            </div>
          </label>

          <label class="radio">
            <input type="radio" name="custody_state" value="at_office">
            <div class="radio-content">
              <span style="display: block;">I left it at a campus office</span>
              <span style="font-size: 0.85rem; color: #6b7280; font-weight: normal;">The owner will claim it directly from the office staff.</span>
            </div>
          </label>
        </div>

        <div id="officeDropdownWrap" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
          <label for="office_id">Which office did you leave it at? <span class="req">*</span></label>
          <select name="office_id" id="office_id">
            <option value="">-- Choose an office --</option>
            <?php foreach ($offices as $office): ?>
              <option value="<?= (int)$office['id'] ?>"><?= h($office['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="step-error" id="step4Error" aria-live="assertive"></div>

        <div class="step-actions">
          <button type="button" class="report-btn-secondary prevBtn">Back</button>
          <button type="button" class="report-btn-primary nextBtn">Continue</button>
        </div>
      </section>

      <section class="report-card" data-step="5" hidden>
        <h2>Finish & Privacy</h2>
        
        <div class="id-extra" style="margin-bottom: 20px;">
          <div class="id-extra-head">
            <strong>Reporting as:</strong>
          </div>
          <p style="margin: 5px 0; color: #374151; font-size: 0.95rem;"><strong>Name:</strong> <?= h($userFullName) ?></p>
          <p style="margin: 5px 0; color: #374151; font-size: 0.95rem;"><strong>Email:</strong> <?= h($userEmail) ?></p>
            <?php if (!empty($userPhone)): ?>
              <p style="margin: 5px 0; color: #374151; font-size: 0.95rem;"><strong>Phone:</strong> <?= h($userPhone) ?></p>
            <?php endif; ?>
          <p class="upload-hint" style="margin-top: 10px; font-style: italic;">These details are pulled securely from your account.</p>
        </div>

        <label>Owner Contact Preference <span class="req">*</span></label>
        <select name="reporter_visibility" id="visSelect">
          <option value="anonymous_to_owner">Keep me anonymous (Owner contacts the office)</option>
          <option value="share_with_owner">Share my contact info with the owner</option>
        </select>

        <div class="step-error" id="step5Error" aria-live="assertive"></div>

        <div class="step-actions">
          <button type="button" class="report-btn-secondary prevBtn">Back</button>
          <button type="submit" class="report-btn-primary">Submit Report</button>
        </div>
      </section>

    </form>
  </div>
  <div class="voice-step-indicator" aria-live="polite" style="display: none;"></div>

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

    // SIMPLE PHOTO UPLOAD - GUARANTEED TO WORK
  const photoInput = document.getElementById('photoInput');
  const dropZone = document.getElementById('dropZone');
  const preview = document.getElementById('previewContainer');
  const step1Error = document.getElementById('step1Error');
  
  // Store files globally
  let selectedFiles = [];

  // Style the drop zone
  dropZone.style.position = 'relative';
  dropZone.style.cursor = 'pointer';
  
  // Make file input work
  photoInput.style.position = 'absolute';
  photoInput.style.top = '0';
  photoInput.style.left = '0';
  photoInput.style.width = '100%';
  photoInput.style.height = '100%';
  photoInput.style.opacity = '0';
  photoInput.style.cursor = 'pointer';
  photoInput.style.zIndex = '10';

  
  // Handle file selection - ONE TIME ONLY
  let isProcessing = false;
  
  photoInput.addEventListener('change', function(e) {
    // Prevent multiple processing
    if (isProcessing) return;
    isProcessing = true;
    
    const files = Array.from(this.files);
    console.log('Files selected:', files.length);
    
    // Clear existing files (replace, don't add)
    selectedFiles = [];
    
    // Add new files (limit to 5 total)
    files.forEach(file => {
      if (selectedFiles.length < 5 && file.type.startsWith('image/')) {
        selectedFiles.push(file);
      }
    });
    
    // Update display
    updatePreviews();
    step1Error.style.display = 'none';
    dropZone.classList.remove('input-error');
    
    console.log('Total files:', selectedFiles.length);
    
    // Clear the input value
    this.value = '';
    
    // Reset processing flag after a short delay
    setTimeout(() => {
      isProcessing = false;
    }, 100);
  });

  // Update previews
  function updatePreviews() {
    preview.innerHTML = '';
    
    selectedFiles.forEach((file, index) => {
      const reader = new FileReader();
      
      reader.onload = function(e) {
const div = document.createElement('div');
div.className = 'preview-tile';
div.style.position = 'relative';
div.style.display = 'block';
div.style.width = '100%';
div.style.margin = '0';
        
const img = document.createElement('img');
img.src = e.target.result;
img.style.width = '100%';
img.style.height = '100%';
img.style.objectFit = 'cover';
img.style.borderRadius = '10px';
img.style.display = 'block';
        
const removeBtn = document.createElement('button');
removeBtn.innerHTML = '×';
removeBtn.style.position = 'absolute';
removeBtn.style.top = '6px';
removeBtn.style.right = '6px';
removeBtn.style.width = '24px';
removeBtn.style.height = '24px';
removeBtn.style.borderRadius = '50%';
removeBtn.style.background = '#C53030';
removeBtn.style.color = 'white';
removeBtn.style.border = '2px solid white';
removeBtn.style.cursor = 'pointer';
removeBtn.style.display = 'flex';
removeBtn.style.alignItems = 'center';
removeBtn.style.justifyContent = 'center';
removeBtn.style.lineHeight = '1';
removeBtn.style.fontSize = '16px';
removeBtn.style.fontWeight = '700';
removeBtn.style.padding = '0';
removeBtn.style.boxShadow = '0 2px 4px rgba(0,0,0,0.2)';
removeBtn.style.zIndex = '2';
        
        removeBtn.onclick = function(e) {
          e.stopPropagation();
          selectedFiles.splice(index, 1);
          updatePreviews();
        };
        
        div.appendChild(img);
        div.appendChild(removeBtn);
        preview.appendChild(div);
      };
      
      reader.readAsDataURL(file);
    });
  }

  // Drag and drop
  let isDragover = false;
  
  dropZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    e.stopPropagation();
    if (!isDragover) {
      dropZone.classList.add('dragover');
      isDragover = true;
    }
  });

  dropZone.addEventListener('dragleave', function(e) {
    e.preventDefault();
    e.stopPropagation();
    dropZone.classList.remove('dragover');
    isDragover = false;
  });

  dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    e.stopPropagation();
    dropZone.classList.remove('dragover');
    isDragover = false;
    
    const dt = e.dataTransfer;
    const files = Array.from(dt.files);
    
    // Clear existing files (replace, don't add)
    selectedFiles = [];
    
    files.forEach(file => {
      if (selectedFiles.length < 5 && file.type.startsWith('image/')) {
        selectedFiles.push(file);
      }
    });
    
    updatePreviews();
  });

function showError(stepErrorId, message, element = null) {
  const errorBox = document.getElementById(stepErrorId);
  errorBox.textContent = message;
  errorBox.style.display = 'block';

  if (element) {
    element.classList.add('input-error');
    element.scrollIntoView({ behavior: "smooth", block: "center" });
  }
}

function clearErrors(stepErrorId) {
  const errorBox = document.getElementById(stepErrorId);
  errorBox.textContent = "";
  errorBox.style.display = "none";

  document.querySelectorAll('.input-error').forEach(el => {
    el.classList.remove('input-error');
  });
}
    
  document.querySelectorAll('.nextBtn').forEach(btn => {
  btn.addEventListener('click', () => {

    // STEP 1 VALIDATION
    if (currentStep === 1) {
      clearErrors('step1Error');

      if (selectedFiles.length === 0) {
        showError(
          'step1Error',
          "Please upload at least one photo of the item.",
          dropZone
        );
        return;
      }
    }

    // STEP 2 VALIDATION
    if (currentStep === 2) {
      clearErrors('step2Error');

      const category = document.getElementById('categoryId');
      const title = document.getElementById('title');

      if (!category.value) {
        showError(
          'step2Error',
          "Please select a category so we know what item was found."
        );
        return;
      }

      if (!title.value.trim()) {
        showError(
          'step2Error',
          "Please enter a short title for the item.",
          title
        );
        return;
      }

      // If ID category
      if (document.getElementById('idExtra') && !document.getElementById('idExtra').hidden) {
        const idType = document.querySelector('[name="id_type"]');
        const nameOnId = document.querySelector('[name="name_on_id"]');

        if (!idType.value.trim()) {
          showError(
            'step2Error',
            "Please enter the type of ID.",
            idType
          );
          return;
        }

        if (!nameOnId.value.trim()) {
          showError(
            'step2Error',
            "Please enter the name shown on the ID.",
            nameOnId
          );
          return;
        }
      }
    }

    // STEP 3 VALIDATION
    if (currentStep === 3) {
      clearErrors('step3Error');

      const location = document.querySelector('[name="found_location_id"]');
      const date = document.querySelector('[name="found_date"]');

      if (!location.value) {
        showError(
          'step3Error',
          "Please select where you found the item.",
          location
        );
        return;
      }

      if (!date.value) {
        showError(
          'step3Error',
          "Please enter the date the item was found.",
          date
        );
        return;
      }
    }

    // STEP 4 VALIDATION
    if (currentStep === 4) {
      clearErrors('step4Error');

      const custody = document.querySelector('input[name="custody_state"]:checked').value;
      const office = document.getElementById('office_id');

      if (custody === 'at_office' && !office.value) {
        showError(
          'step4Error',
          "Please select the office where you left the item.",
          office
        );
        return;
      }
    }

    if (currentStep < 5) {
      showStep(currentStep + 1);
    }

  });
});

  // Form submit - attach files to input
  document.getElementById('reportForm').addEventListener('submit', function(e) {
      // First, make sure files are attached
      if (selectedFiles.length > 0) {
          const dt = new DataTransfer();
          selectedFiles.forEach(file => dt.items.add(file));
          photoInput.files = dt.files;
          console.log('Submitting with', photoInput.files.length, 'files');
      } else {
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
      document.querySelectorAll('.cat').forEach(cat => {
        cat.addEventListener('click', function(e) {
          e.preventDefault();
          
          document.querySelectorAll('.cat').forEach(c => c.classList.remove('active'));
          this.classList.add('active');
          categoryId.value = this.dataset.id;
          
          if (this.dataset.name.toLowerCase() === 'ids') {
            idExtra.hidden = false;
          } else {
            idExtra.hidden = true;
          }
        });
      });

    // Handle Back Buttons
    document.querySelectorAll('.prevBtn').forEach(btn => {
      btn.addEventListener('click', () => {
        if (currentStep > 1) {
          showStep(currentStep - 1);
        }
      });
    });
      
      // Restore selected category
      const savedId = categoryId.value;
      if (savedId) {
        const selected = document.querySelector(`.cat[data-id="${savedId}"]`);
        if (selected) {
          selected.classList.add('active');
          if (selected.dataset.name.toLowerCase() === 'ids') {
            idExtra.hidden = false;
          }
        }
      }
    }
  });

  // Office dropdown toggle
  document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('input[name="custody_state"]');
    const officeWrap = document.getElementById('officeDropdownWrap');
    const officeSelect = document.getElementById('office_id');

    radios.forEach(radio => {
      radio.addEventListener('change', function() {
        if (this.value === 'at_office') {
          officeWrap.style.display = 'block';
          officeSelect.required = true;
        } else {
          officeWrap.style.display = 'none';
          officeSelect.required = false;
          officeSelect.value = '';
        }
      });
    });
  });

   // Voice step navigation enhancement
    document.addEventListener('DOMContentLoaded', function() {
        // Announce current step when page loads
        const currentStep = document.querySelector('.step.active');
        if (currentStep && window.voiceCommands) {
            setTimeout(() => {
                window.voiceCommands.speak(`You are on step ${currentStep.dataset.step}: ${currentStep.textContent}`);
            }, 1000);
        }
        
        // Announce step changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.target.classList.contains('active')) {
                    const stepText = mutation.target.textContent;
                    const stepNum = mutation.target.dataset.step;
                    if (window.voiceCommands) {
                        window.voiceCommands.speak(`Step ${stepNum}: ${stepText}`);
                    }
                }
            });
        });
        
        document.querySelectorAll('.step').forEach(step => {
            observer.observe(step, { attributes: true, attributeFilter: ['class'] });
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>   