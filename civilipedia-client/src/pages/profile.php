<?php
if (!Auth::isLoggedIn()) {
    header('Location: /?page=login');
    exit;
}

$api  = new ApiClient();

$avatarError   = $_SESSION['avatar_error']   ?? '';
$avatarSuccess = $_SESSION['avatar_success'] ?? '';
unset($_SESSION['avatar_error'], $_SESSION['avatar_success']);

$error   = '';
$success = '';

//  Changement de mot de passe 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'password') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new) || empty($confirm)) {
        $error = 'Tous les champs sont requis.';
    } elseif ($new !== $confirm) {
        $error = 'Les nouveaux mots de passe ne correspondent pas.';
    } elseif (strlen($new) < 6) {
        $error = 'Le nouveau mot de passe doit faire au moins 6 caractères.';
    } else {
        $response = $api->patch('/users/me/password', [
            'current_password' => $current,
            'new_password'     => $new,
        ], Auth::getToken());

        if ($response['status'] === 200) {
            $success = 'Mot de passe mis à jour avec succès.';
        } else {
            $error = $response['body']['error']['message'] ?? 'Mot de passe actuel incorrect.';
        }
    }
}

// Relire la session (mise à jour par profile_avatar.php après un upload)
$user = Auth::getUser();

// L'API stocke le filename brut (ex: "avatar_abc123.jpg").
// Le proxy public/avatar.php sert le fichier hors webroot.
$avatarFilename = $user['avatar'] ?? null;
$avatarSrc = $avatarFilename
    ? '/avatar.php?file=' . urlencode($avatarFilename) . '&t=' . time()
    : null;
?>

<?php if ($avatarError):   ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($avatarError) ?>, 'danger'));
    </script>
<?php endif; ?>
<?php if ($avatarSuccess): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($avatarSuccess) ?>, 'success'));
    </script>
<?php endif; ?>
<?php if ($error):   ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($error) ?>, 'danger'));
    </script>
<?php endif; ?>
<?php if ($success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($success) ?>, 'success'));
    </script>
<?php endif; ?>

<div class="profile-layout">
    <div class="container">

        <!--  En-tête  -->
        <div class="profile-header">

            <!-- Avatar cliquable -->
            <label class="profile-avatar" for="avatar-quick-input" title="Cliquer pour modifier">
                <?php if ($avatarSrc): ?>
                    <img src="<?= htmlspecialchars($avatarSrc) ?>"
                        alt=""
                        onerror="this.style.display='none'; document.getElementById('avatar-fallback').style.display='flex';">
                    <span id="avatar-fallback"
                        style="display:none; align-items:center; justify-content:center; width:100%; height:100%;">
                        <i class="fa-solid fa-user"></i>
                    </span>
                <?php else: ?>
                    <i class="fa-solid fa-user"></i>
                <?php endif; ?>
                <div class="profile-avatar__overlay">
                    <span>Cliquer pour<br>modifier</span>
                </div>
            </label>

            <!-- Form avatar caché -->
            <form method="POST" action="/?page=profile_avatar"
                enctype="multipart/form-data" id="avatar-quick-form">
                <input type="file" id="avatar-quick-input" name="avatar"
                    accept="image/jpeg,image/png,image/webp"
                    onchange="document.getElementById('avatar-quick-form').submit()">
            </form>

            <div class="profile-info">
                <h1 class="profile-info__name"><?= htmlspecialchars($user['username']) ?></h1>
                <div class="profile-info__meta">
                    <span>
                        <i class="fa-solid fa-<?= $user['role'] === 'admin' ? 'shield-halved' : 'circle-user' ?>"></i>
                        <?= htmlspecialchars($user['role']) ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">

                <!-- Mot de passe -->
                <div class="card profile-section">
                    <div class="card-body">
                        <h2 class="profile-section__title">
                            <i class="fa-solid fa-lock"></i> Mot de passe
                        </h2>

                        <form method="POST" action="/?page=profile">
                            <input type="hidden" name="action" value="password">
                            <div class="mb-3">
                                <label class="form-label" for="current_password">Mot de passe actuel</label>
                                <input type="password" id="current_password" name="current_password"
                                    class="form-control" placeholder="••••••••"
                                    required autocomplete="current-password">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="new_password">Nouveau mot de passe</label>
                                <input type="password" id="new_password" name="new_password"
                                    class="form-control" placeholder="••••••••"
                                    required autocomplete="new-password">
                            </div>
                            <div class="mb-4">
                                <label class="form-label" for="confirm_password">Confirmer</label>
                                <input type="password" id="confirm_password" name="confirm_password"
                                    class="form-control" placeholder="••••••••"
                                    required autocomplete="new-password">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>