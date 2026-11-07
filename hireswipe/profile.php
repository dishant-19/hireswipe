<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_login();
if (!$me['role']) redirect('onboarding.php');

$isSeeker = $me['role'] === 'seeker';
$table = $isSeeker ? 'seeker_profiles' : 'employer_profiles';
$s = db()->prepare("SELECT * FROM $table WHERE user_id = ?");
$s->execute([$me['id']]);
$p = $s->fetch() ?: [];
$firstTime = !$me['onboarded'];

$errors = [];
$in = $p;                         // values shown in the form

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $loc  = trim($_POST['location'] ?? '');
    $type = $_POST['employment_type'] ?? '';
    $in = $_POST + $p;
    $in['name'] = $name;

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors[] = 'Please enter a valid name.';
    if ($loc === '' || mb_strlen($loc) > 120)            $errors[] = 'Please enter your location.';
    if (!isset(EMPLOYMENT_TYPES[$type]))                 $errors[] = 'Please choose an employment type.';

    if ($isSeeker) {
        $age   = (int)($_POST['age'] ?? 0);
        $nat   = trim($_POST['nationality'] ?? '');
        $qual  = trim($_POST['qualification'] ?? '');
        $exp   = $_POST['experience_years'] ?? '';
        $sal   = $_POST['expected_salary'] ?? '';
        $inds  = array_values(array_intersect(INDUSTRIES, (array)($_POST['industries'] ?? [])));
        $head  = trim($_POST['headline'] ?? '');
        $bio   = trim($_POST['bio'] ?? '');

        if ($age < 16 || $age > 80)                    $errors[] = 'Age must be between 16 and 80.';
        if ($nat === '' || mb_strlen($nat) > 80)       $errors[] = 'Please enter your nationality.';
        if ($qual === '' || mb_strlen($qual) > 150)    $errors[] = 'Please enter your qualification.';
        if (!is_numeric($exp) || $exp < 0 || $exp > 60) $errors[] = 'Experience must be a number of years (0 or more).';
        if (!ctype_digit((string)$sal) || (int)$sal > 10000000) $errors[] = 'Please enter your expected monthly salary as a number.';
        if (!$inds)                                    $errors[] = 'Choose at least one industry.';
        if (mb_strlen($head) > 150)                    $errors[] = 'Headline is too long (150 characters max).';
        if (mb_strlen($bio) > 1000)                    $errors[] = 'About you is too long (1000 characters max).';

        $photo = save_upload('photo', ['jpg', 'jpeg', 'png', 'webp'], 2 * 1048576, $errors, 'Profile picture');
        $cv    = save_upload('cv', ['pdf', 'doc', 'docx'], 3 * 1048576, $errors, 'CV');
        if ($firstTime && !($p['photo'] ?? null) && !$photo && !$errors) $errors[] = 'Please upload a profile picture.';
        if ($firstTime && !($p['cv'] ?? null) && !$cv && !$errors)       $errors[] = 'Please upload your CV.';

        if (!$errors) {
            $photo = $photo ?: ($p['photo'] ?? null);
            $cv    = $cv    ?: ($p['cv'] ?? null);
            db()->prepare(
                'INSERT INTO seeker_profiles (user_id, age, location, nationality, qualification, experience_years, expected_salary, employment_type, industries, headline, bio, photo, cv)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE age=VALUES(age), location=VALUES(location), nationality=VALUES(nationality),
                   qualification=VALUES(qualification), experience_years=VALUES(experience_years), expected_salary=VALUES(expected_salary),
                   employment_type=VALUES(employment_type), industries=VALUES(industries), headline=VALUES(headline), bio=VALUES(bio),
                   photo=VALUES(photo), cv=VALUES(cv)'
            )->execute([$me['id'], $age, $loc, $nat, $qual, $exp, (int)$sal, $type, implode(',', $inds), $head ?: null, $bio ?: null, $photo, $cv]);
        }
    } else {
        $company = trim($_POST['company_name'] ?? '');
        $industry = $_POST['industry'] ?? '';
        $position = trim($_POST['position'] ?? '');
        $sal  = $_POST['salary'] ?? '';
        $detail = trim($_POST['job_detail'] ?? '');

        if ($company === '' || mb_strlen($company) > 150)  $errors[] = 'Please enter your company name.';
        if (!in_array($industry, INDUSTRIES, true))        $errors[] = 'Please choose an industry.';
        if ($position === '' || mb_strlen($position) > 150) $errors[] = 'Please enter the position you are hiring for.';
        if (!ctype_digit((string)$sal) || (int)$sal > 10000000) $errors[] = 'Please enter the monthly salary as a number.';
        if (mb_strlen($detail) < 20 || mb_strlen($detail) > 2000) $errors[] = 'Job details must be 20 to 2000 characters.';

        $logo = save_upload('logo', ['jpg', 'jpeg', 'png', 'webp'], 2 * 1048576, $errors, 'Company logo');
        if ($firstTime && !($p['logo'] ?? null) && !$logo && !$errors) $errors[] = 'Please upload your company logo.';

        if (!$errors) {
            $logo = $logo ?: ($p['logo'] ?? null);
            db()->prepare(
                'INSERT INTO employer_profiles (user_id, company_name, location, industry, position, employment_type, salary, job_detail, logo)
                 VALUES (?,?,?,?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE company_name=VALUES(company_name), location=VALUES(location), industry=VALUES(industry),
                   position=VALUES(position), employment_type=VALUES(employment_type), salary=VALUES(salary),
                   job_detail=VALUES(job_detail), logo=VALUES(logo)'
            )->execute([$me['id'], $company, $loc, $industry, $position, $type, (int)$sal, $detail, $logo]);
        }
    }

    if (!$errors) {
        db()->prepare('UPDATE users SET name = ?, onboarded = 1 WHERE id = ?')->execute([$name, $me['id']]);
        flash('success', $firstTime ? 'Your profile is ready. Start swiping!' : 'Profile updated.');
        redirect($firstTime ? 'swipe.php' : 'profile.php');
    }
}

$val = fn(string $k, $d = '') => e($in[$k] ?? $d);
$selInds = split_list($in['industries'] ?? '');
if (isset($_POST['industries'])) $selInds = (array)$_POST['industries'];

$pageTitle = $firstTime ? 'Set up your profile' : 'My profile';
require __DIR__ . '/includes/header.php';
?>
<div class="card form-card wide">
  <h2><?= $firstTime ? 'Tell us about ' . ($isSeeker ? 'yourself' : 'your company') : 'My profile' ?></h2>
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>

  <form method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <label>Your name
      <input type="text" name="name" maxlength="100" value="<?= e($in['name'] ?? $me['name']) ?>" required>
    </label>

<?php if ($isSeeker): ?>
    <label>Headline <small>(optional, e.g. "Junior PHP developer")</small>
      <input type="text" name="headline" maxlength="150" value="<?= $val('headline') ?>">
    </label>
    <div class="grid2">
      <label>Age
        <input type="number" name="age" min="16" max="80" value="<?= $val('age') ?>" required>
      </label>
      <label>Nationality
        <input type="text" name="nationality" maxlength="80" value="<?= $val('nationality') ?>" required>
      </label>
    </div>
    <label>Location (city, state)
      <input type="text" name="location" maxlength="120" value="<?= $val('location') ?>" required>
    </label>
    <label>Highest qualification
      <input type="text" name="qualification" maxlength="150" value="<?= $val('qualification') ?>" required>
    </label>
    <div class="grid2">
      <label>Experience (years)
        <input type="number" name="experience_years" min="0" max="60" step="0.5" value="<?= $val('experience_years', '0') ?>" required>
      </label>
      <label>Expected salary per month (<?= CURRENCY ?>)
        <input type="number" name="expected_salary" min="0" step="500" value="<?= $val('expected_salary') ?>" required>
      </label>
    </div>
    <label>Employment type you want
      <select name="employment_type" required>
        <option value="">Choose…</option>
        <?php foreach (EMPLOYMENT_TYPES as $k => $l): ?>
          <option value="<?= $k ?>" <?= ($in['employment_type'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <fieldset>
      <legend>Industries you like</legend>
      <div class="chips">
        <?php foreach (INDUSTRIES as $ind): ?>
          <label class="chip"><input type="checkbox" name="industries[]" value="<?= e($ind) ?>" <?= in_array($ind, $selInds, true) ? 'checked' : '' ?>><span><?= e($ind) ?></span></label>
        <?php endforeach; ?>
      </div>
    </fieldset>
    <label>About you <small>(optional)</small>
      <textarea name="bio" rows="4" maxlength="1000"><?= $val('bio') ?></textarea>
    </label>
    <div class="grid2">
      <label>Profile picture <small>(JPG, PNG or WebP, up to 2 MB)</small>
        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
        <?php if (!empty($p['photo'])): ?><span class="current"><?= avatar($me['name'], $p['photo'], 'avatar-sm') ?> Current picture saved</span><?php endif; ?>
      </label>
      <label>CV <small>(PDF, DOC or DOCX, up to 3 MB)</small>
        <input type="file" name="cv" accept=".pdf,.doc,.docx">
        <?php if (!empty($p['cv'])): ?><span class="current">✔ CV saved · <a href="<?= e(file_url($p['cv'])) ?>" target="_blank" rel="noopener">view</a></span><?php endif; ?>
      </label>
    </div>

<?php else: ?>
    <label>Company name
      <input type="text" name="company_name" maxlength="150" value="<?= $val('company_name') ?>" required>
    </label>
    <div class="grid2">
      <label>Location (city, state)
        <input type="text" name="location" maxlength="120" value="<?= $val('location') ?>" required>
      </label>
      <label>Industry
        <select name="industry" required>
          <option value="">Choose…</option>
          <?php foreach (INDUSTRIES as $ind): ?>
            <option <?= ($in['industry'] ?? '') === $ind ? 'selected' : '' ?>><?= e($ind) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>
    <label>Position you are hiring for
      <input type="text" name="position" maxlength="150" value="<?= $val('position') ?>" required>
    </label>
    <div class="grid2">
      <label>Employment type
        <select name="employment_type" required>
          <option value="">Choose…</option>
          <?php foreach (EMPLOYMENT_TYPES as $k => $l): ?>
            <option value="<?= $k ?>" <?= ($in['employment_type'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Salary offered per month (<?= CURRENCY ?>)
        <input type="number" name="salary" min="0" step="500" value="<?= $val('salary') ?>" required>
      </label>
    </div>
    <label>Job details <small>(20 to 2000 characters)</small>
      <textarea name="job_detail" rows="6" maxlength="2000" required><?= $val('job_detail') ?></textarea>
    </label>
    <label>Company logo <small>(JPG, PNG or WebP, up to 2 MB)</small>
      <input type="file" name="logo" accept="image/jpeg,image/png,image/webp">
      <?php if (!empty($p['logo'])): ?><span class="current"><?= avatar($in['company_name'] ?? $me['name'], $p['logo'], 'avatar-sm') ?> Current logo saved</span><?php endif; ?>
    </label>
<?php endif; ?>

    <button class="btn btn-block"><?= $firstTime ? 'Save and start swiping' : 'Save changes' ?></button>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
