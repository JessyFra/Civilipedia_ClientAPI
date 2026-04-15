<?php
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api      = new ApiClient();
    $response = $api->post('/auth/login', [
        'username' => trim($_POST['username'] ?? ''),
        'password' => $_POST['password'] ?? '',
    ]);

    if ($response['status'] === 200 && !empty($response['body']['token'])) {
        Auth::setToken($response['body']['token']);
        $me = $api->get('/users/me', $response['body']['token']);
        if (!empty($me['body'])) {
            Auth::setUser($me['body']);
        }
        header('Location: /?page=home');
        exit;
    } else {
        $error = 'Identifiants incorrects.';
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
                    <i class="fa-solid fa-user"></i>
                </div>

                <h1 class="auth-card__title">Connexion</h1>
                <p class="auth-card__subtitle">Accédez à votre espace Civilipédia.</p>

                <form method="POST" action="/?page=login">
                    <div class="mb-3">
                        <label class="form-label" for="username">Nom d'utilisateur</label>
                        <input type="text" id="username" name="username" class="form-control"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            placeholder="votre_pseudo" required autofocus autocomplete="username">
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" class="form-control"
                            placeholder="••••••••" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Se connecter
                    </button>
                </form>

                <div class="auth-card__footer">
                    Pas encore de compte ? <a href="/?page=register">S'inscrire</a>
                </div>
            </div>
        </div>
    </div>
</div>