<?php
$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Tous les champs sont requis.';
    } elseif ($password !== $confirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit faire au moins 6 caractères.';
    } else {
        $api = new ApiClient();
        $response = $api->post('/auth/register', [
            'username' => $username,
            'password' => $password,
        ]);

        if ($response['status'] === 201) {
            $success = true;
        } elseif ($response['status'] === 409) {
            $error = 'Ce nom d\'utilisateur est déjà pris.';
        } else {
            $error = 'Une erreur est survenue, veuillez réessayer.';
        }
    }
}
?>

<?php if ($error): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($error) ?>, 'danger'));
    </script>
<?php endif; ?>

<div class="auth-layout">
    <div class="auth-card">
        <div class="card">
            <div class="card-body">

                <div class="auth-card__icon">
                    <i class="fa-solid fa-user-plus"></i>
                </div>

                <h1 class="auth-card__title">Inscription</h1>
                <p class="auth-card__subtitle">Créez votre compte et contribuez au wiki.</p>

                <?php if (!$success): ?>
                    <form method="POST" action="/?page=register">
                        <div class="mb-3">
                            <label class="form-label" for="username">Nom d'utilisateur</label>
                            <input type="text" id="username" name="username" class="form-control"
                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                placeholder="votre_pseudo" required autofocus autocomplete="username">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">Mot de passe</label>
                            <input type="password" id="password" name="password" class="form-control"
                                placeholder="••••••••" required autocomplete="new-password">
                            <span class="form-text">6 caractères minimum.</span>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="confirm_password">Confirmer le mot de passe</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                placeholder="••••••••" required autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa-solid fa-user-plus"></i> Créer mon compte
                        </button>
                    </form>

                <?php else: ?>
                    <div style="text-align:center; padding: var(--space-8) 0;">
                        <i class="fa-solid fa-circle-check" style="font-size:2.5rem; color:var(--color-success); display:block; margin-bottom:var(--space-4);"></i>
                        <p style="color:var(--color-text-muted); margin:0;">
                            Compte créé avec succès !<br>
                            <a href="/?page=login" style="color:var(--color-text); font-weight:var(--weight-semibold);">Se connecter →</a>
                        </p>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => showToast('Compte créé avec succès !', 'success'));
                    </script>
                <?php endif; ?>

                <div class="auth-card__footer">
                    Déjà un compte ? <a href="/?page=login">Se connecter</a>
                </div>
            </div>
        </div>
    </div>
</div>