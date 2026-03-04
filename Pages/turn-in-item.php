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
$offices    = $pdo->query("SELECT id, name, location FROM offices WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

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

  // Server validation (still needed even with JS)
  if ($category_id <= 0) $errors[] = "Please choose a category.";
  if ($title === '') $errors[] = "Please enter a short title.";
  if ($found_location_id <= 0) $errors[] = "Please select where you found the item.";
  if ($found_date === '') $errors[] = "Please select the date found.";
  if (!filter_var($reporter_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email.";
  if ($reporter_phone === '') $errors[] = "Please enter a phone number.";

  $allowedCustody = ['with_finder','at_office'];
  if (!in_array($custody_state, $allowedCustody, true)) $errors[] = "Invalid custody value.";
  if ($custody_state === 'at_office' && $office_id <= 0) $errors[] = "Please select which office is holding the item.";

  $allowedVis = ['anonymous_to_owner','share_with_owner'];
  if (!in_array($visibility, $allowedVis, true)) $errors[] = "Invalid privacy choice.";

  // Photos required: at least 1, up to 5
  $photoCount = 0;
  if ($files && isset($files['name']) && is_array($files['name'])) {
    foreach ($files['name'] as $n) if ($n !== '') $photoCount++;
  }
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
        $nameGuess = strstr($reporter_email, '@', true) ?: 'Reporter';
        $pdo->prepare("
          INSERT INTO users (role, full_name, email, phone, is_active)
          VALUES ('student', :full_name, :email, :phone, 1)
        ")->execute([
          ':full_name' => $nameGuess,
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
          reported_by_user_id, reporter_visibility
        ) VALUES (
          :category_id, 'recent', :title, :description,
          :found_location_id, :found_at_detail,
          :found_date, :found_time,
          :custody_state, :office_id,
          :reported_by_user_id, :reporter_visibility
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
        ':reported_by_user_id' => $reporter_user_id,
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

      for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['name'][$i] === '') continue;

        $tmp = $files['tmp_name'][$i];
        $err = $files['error'][$i];
        if ($err !== UPLOAD_ERR_OK) throw new RuntimeException("Upload failed.");

        $origName = $files['name'][$i];
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

      <!-- STEP 1 -->
      <section class="report-card" data-step="1">
        <h2>Upload Photos</h2>
        <p class="helper">Upload 1–5 photos. Clear photos help the owner recognize it faster.</p>

        <div class="upload-zone" id="dropZone">
          <input type="file" id="photoInput" name="photos[]" accept="image/*" multiple hidden>
          <div class="zone-content">
            <span class="zone-icon">📷</span>
            <p><strong>Click to upload</strong> or drag and drop</p>
            <span class="upload-hint">JPG, PNG, WEBP (Max 5 photos)</span>
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
                <?= h($o['name']) ?><?= $o['location'] ? ' — ' . h($o['location']) : '' ?>
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
  // ------- Stepper core -------
  let currentStep = Math.min(Math.max(parseInt(document.getElementById('currentStepInput').value || '1', 10), 1), 5);

  const cards = document.querySelectorAll('.report-card');
  const steps = document.querySelectorAll('.step');
  const stepInput = document.getElementById('currentStepInput');

  function showStep(step) {
    currentStep = step;
    stepInput.value = String(step);

    cards.forEach(c => c.classList.remove('active'));
    steps.forEach(s => s.classList.remove('active'));

    document.querySelector(`.report-card[data-step="${step}"]`).classList.add('active');
    document.querySelector(`.step[data-step="${step}"]`).classList.add('active');

    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  // Clickable Stepper (Go back functionality)
  steps.forEach(s => {
    s.addEventListener('click', () => {
      const targetStep = parseInt(s.dataset.step);
      // Allow going back immediately without validation
      if (targetStep < currentStep) {
        showStep(targetStep);
      }
      // Note: Going forward via stepper is disabled to force validation via "Continue" buttons
    });
  });

  // init
  showStep(currentStep);

  // ------- Photo append + Drag & Drop -------
  const dropZone = document.getElementById('dropZone');
  const photoInput = document.getElementById('photoInput');
  const preview = document.getElementById('previewContainer');

  let selectedFiles = [];

  // Click to upload
  dropZone.addEventListener('click', () => photoInput.click());

  photoInput.addEventListener('change', () => {
    handleFiles(Array.from(photoInput.files || []));
  });

  // Drag & Drop events
  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
  });

  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  ['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, highlight, false);
  });

  ['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, unhighlight, false);
  });

  function highlight() { dropZone.classList.add('dragover'); }
  function unhighlight() { dropZone.classList.remove('dragover'); }

  dropZone.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    handleFiles(Array.from(dt.files));
  });

  function handleFiles(newFiles) {
    if (!newFiles.length) return;

    for (const f of newFiles) {
      if (selectedFiles.length >= 5) break;
      // Simple type check
      if(f.type.startsWith('image/')) {
        selectedFiles.push(f);
      }
    }
    updateInputFiles();
    renderPreviews();
  }

  function updateInputFiles() {
    const dt = new DataTransfer();
    selectedFiles.forEach(f => dt.items.add(f));
    photoInput.files = dt.files;
  }

  function renderPreviews() {
    preview.innerHTML = '';

    selectedFiles.forEach((file, idx) => {
      const tile = document.createElement('div');
      tile.className = 'preview-tile';

      const img = document.createElement('img');
      img.src = URL.createObjectURL(file);

      const rm = document.createElement('button');
      rm.type = 'button';
      rm.className = 'preview-remove';
      rm.setAttribute('aria-label', 'Remove photo');
      // No text, CSS will add icon/styling

      rm.addEventListener('click', (e) => {
        e.stopPropagation(); // prevent triggering dropZone click
        selectedFiles.splice(idx, 1);
        updateInputFiles();
        renderPreviews();
      });

      tile.appendChild(img);
      tile.appendChild(rm);
      preview.appendChild(tile);
    });
  }

  // ------- Category selection (and ID extra visibility) -------
  const categoryGrid = document.getElementById('categoryGrid');
  const categoryId = document.getElementById('categoryId');
  const idExtra = document.getElementById('idExtra');

  function isIDs(name) {
    return (name || '').trim().toLowerCase() === 'ids';
  }

  // Restore active category UI if present
  (function restoreCategoryUI(){
    const activeId = categoryId.value;
    if (!activeId) return;
    const btn = categoryGrid.querySelector(`.cat[data-id="${activeId}"]`);
    if (btn) btn.classList.add('active');
  })();

  categoryGrid.addEventListener('click', (e) => {
    const btn = e.target.closest('.cat');
    if (!btn) return;

    categoryGrid.querySelectorAll('.cat').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    categoryId.value = btn.dataset.id || '';

    if (isIDs(btn.dataset.name)) {
      idExtra.hidden = false;
    } else {
      idExtra.hidden = true;
      document.getElementById('idType').value = '';
      document.getElementById('nameOnId').value = '';
    }
  });

  // ------- Custody / office toggle -------
  const officeWrap = document.getElementById('officeWrap');
  const officeSelect = document.getElementById('officeSelect');
  document.querySelectorAll('input[name="custody_state"]').forEach(r => {
    r.addEventListener('change', () => {
      const isAtOffice = document.querySelector('input[name="custody_state"][value="at_office"]').checked;
      officeWrap.hidden = !isAtOffice;
    });
  });

  // ------- Step validation (fix #1) -------
  function setError(step, msg) {
    const el = document.getElementById(`step${step}Error`);
    if (!el) return;
    el.textContent = msg || '';
    el.style.display = msg ? 'block' : 'none';
  }

  function toggleErrorClass(id, hasError) {
    const el = document.getElementById(id);
    if(el) {
      if(hasError) el.classList.add('input-error');
      else el.classList.remove('input-error');
    }
  }

  function validateStep(step) {
    setError(step, '');
    // Reset borders
    document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));

    if (step === 1) {
      if (selectedFiles.length < 1) {
        setError(1, 'Please upload at least 1 photo to continue.');
        document.getElementById('dropZone').classList.add('input-error');
        return false;
      }
      return true;
    }

    if (step === 2) {
      const cat = (categoryId.value || '').trim();
      const titleInput = document.getElementById('titleInput');
      const title = (titleInput.value || '').trim();

      let valid = true;

      if (!cat) {
        setError(2, 'Please choose a category to continue.');
        // categoryGrid isn't an input, just show text error
        valid = false;
      }

      // If IDs category, require id_type + name_on_id
      const catBtn = categoryGrid.querySelector(`.cat[data-id="${cat}"]`);
      const isIds = catBtn ? isIDs(catBtn.dataset.name) : false;

      if (isIds) {
        const idTypeInput = document.getElementById('idType');
        const nameOnIdInput = document.getElementById('nameOnId');
        
        if (!idTypeInput.value.trim()) {
          toggleErrorClass('idType', true);
          valid = false;
        }
        if (!nameOnIdInput.value.trim()) {
          toggleErrorClass('nameOnId', true);
          valid = false;
        }
        if (!valid) {
          setError(2, 'For ID items, please fill the required ID details (type and name).');
        }
      }

      if (!title) {
        toggleErrorClass('titleInput', true);
        if(valid) setError(2, 'Please enter a short title to continue.');
        valid = false;
      }

      return valid;
    }

    if (step === 3) {
      const locInput = document.getElementById('foundLocation');
      const dateInput = document.getElementById('foundDate');
      let valid = true;

      if (!locInput.value.trim()) {
        toggleErrorClass('foundLocation', true);
        valid = false;
      }
      if (!dateInput.value.trim()) {
        toggleErrorClass('foundDate', true);
        valid = false;
      }

      if(!valid) setError(3, 'Please select a location and date found to continue.');
      return valid;
    }

    if (step === 4) {
      const isAtOffice = document.querySelector('input[name="custody_state"][value="at_office"]').checked;
      if (isAtOffice && !(officeSelect.value || '').trim()) {
        toggleErrorClass('officeSelect', true);
        setError(4, 'Please select the office holding the item to continue.');
        return false;
      }
      return true;
    }

    if (step === 5) {
      const emailInput = document.getElementById('emailInput');
      const phoneInput = document.getElementById('phoneInput');
      const visSelect = document.getElementById('visSelect');
      let valid = true;

      if (!emailInput.value.trim()) { toggleErrorClass('emailInput', true); valid = false; }
      if (!phoneInput.value.trim()) { toggleErrorClass('phoneInput', true); valid = false; }
      if (!visSelect.value.trim()) { toggleErrorClass('visSelect', true); valid = false; }

      if (!valid) setError(5, 'Please complete your contact details before submitting.');
      return valid;
    }

    return true;
  }

  // Override next/prev behavior
  document.querySelectorAll('.nextBtn').forEach(btn => {
    btn.addEventListener('click', () => {
      if (!validateStep(currentStep)) return;
      if (currentStep < 5) showStep(currentStep + 1);
    });
  });

  document.querySelectorAll('.prevBtn').forEach(btn => {
    btn.addEventListener('click', () => {
      if (currentStep > 1) showStep(currentStep - 1);
    });
  });

</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>