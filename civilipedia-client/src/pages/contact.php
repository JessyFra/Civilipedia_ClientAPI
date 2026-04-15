<?php
$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Tous les champs sont requis.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } else {
        $api      = new ApiClient();
        $response = $api->post('/contact', [
            'name'    => $name,
            'email'   => $email,
            'subject' => $subject,
            'message' => $message,
        ]);

        if ($response['status'] === 201) {
            $success = true;
        } else {
            $error = 'Une erreur est survenue, veuillez réessayer.';
        }
    }
}

$user = Auth::getUser();
?>

<?php if ($error): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($error) ?>, 'danger'));
    </script>
<?php endif; ?>
<?php if ($success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => showToast('Votre message a bien été envoyé. Merci !', 'success', 6000));
    </script>
<?php endif; ?>

<div class="contact-layout">
    <div class="container">
        <div class="contact-grid">

            <!--  Colonne info gauche  -->
            <div class="contact-info">
                <h1 class="contact-info__title">Nous contacter</h1>
                <p class="contact-info__text">
                    Une question, une suggestion, une erreur à signaler ?<br>
                    Écrivez-nous, nous vous répondrons dans les meilleurs délais.
                </p>

                <div class="contact-info__item">
                    <i class="fa-regular fa-envelope"></i>
                    contact@civilipedia.fr
                </div>
                <div class="contact-info__item">
                    <i class="fa-solid fa-globe"></i>
                    civilipedia.fr
                </div>
                <div class="contact-info__item">
                    <i class="fa-brands fa-github"></i>
                    github.com/civilipedia
                </div>
            </div>

            <!--  Formulaire droite  -->
            <div class="card">
                <div class="card-body">

                    <?php if ($success): ?>
                        <!-- État succès : message dans la card aussi -->
                        <div style="text-align:center; padding: var(--space-10) 0;">
                            <i class="fa-solid fa-paper-plane"
                                style="font-size:2.5rem; color:var(--color-success); display:block; margin-bottom:var(--space-4);"></i>
                            <p style="font-size:var(--text-base); color:var(--color-text-muted); margin:0;">
                                Message envoyé avec succès !<br>
                                <a href="/?page=home"
                                    style="color:var(--color-text); font-weight:var(--weight-semibold);">
                                    Retour à l'accueil →
                                </a>
                            </p>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="/?page=contact">

                            <div class="mb-3">
                                <label class="form-label" for="name">Nom</label>
                                <input type="text" id="name" name="name" class="form-control"
                                    value="<?= htmlspecialchars($_POST['name'] ?? $user['username'] ?? '') ?>"
                                    placeholder="Votre nom" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control"
                                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                    placeholder="votre@email.fr" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="subject">Sujet</label>
                                <input type="text" id="subject" name="subject" class="form-control"
                                    value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"
                                    placeholder="Objet de votre message" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label" for="message">Message</label>
                                <textarea id="message" name="message" class="form-control"
                                    rows="5" placeholder="Votre message…"
                                    required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fa-solid fa-paper-plane"></i>
                                Envoyer le message
                            </button>

                        </form>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>
</div>