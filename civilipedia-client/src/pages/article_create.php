<?php
if (!Auth::isLoggedIn()) {
    header('Location: /?page=login');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title']   ?? '');
    $content =      $_POST['content'] ?? '';

    if (empty($title) || empty($content)) {
        $error = 'Le titre et le contenu sont requis.';
    } else {
        $api      = new ApiClient();
        $response = $api->post('/articles', [
            'title'   => $title,
            'content' => $content,
        ], Auth::getToken());

        if ($response['status'] === 201) {
            $newId = $response['body']['id'] ?? null;

            if ($newId && isset($_FILES['article_image']) && $_FILES['article_image']['error'] === UPLOAD_ERR_OK) {
                $api->uploadFile(
                    '/articles/' . $newId . '/image',
                    $_FILES['article_image']['tmp_name'],
                    $_FILES['article_image']['type'],
                    'image',
                    Auth::getToken()
                );
            }

            header('Location: /?page=article&id=' . $newId);
            exit;
        } else {
            $error = $response['body']['error']['message'] ?? 'Erreur lors de la création.';
        }
    }
}
?>

<?php if ($error): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($error) ?>, 'danger'));
    </script>
<?php endif; ?>

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<section class="article-create-layout">
    <div class="container">

        <!-- Titre -->
        <div class="row mb-4">
            <div class="col-12">
                <input type="text"
                    id="create-title"
                    class="create-title-input"
                    value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                    placeholder="Titre de l'article…"
                    required autofocus>
            </div>
        </div>

        <!-- Contenu + Sidebar -->
        <form id="create-form" method="POST" action="/?page=article_create" enctype="multipart/form-data">
            <input type="hidden" name="title" id="form-title">
            <input type="hidden" name="content" id="form-content">
            <input type="file"
                name="article_image" id="form-image-file"
                accept="image/jpeg,image/png,image/webp,image/gif"
                class="d-none"
                onchange="previewImage(this)">

            <div class="row g-5">

                <!-- Éditeur (8/12) -->
                <div class="col-lg-8 order-last order-lg-first">
                    <div id="quill-editor"></div>
                </div>

                <!-- Sidebar (4/12) -->
                <div class="col-lg-4 order-first order-lg-last">
                    <div class="article-sidebar">

                        <!-- Image de couverture -->
                        <div class="sidebar-block">
                            <p class="sidebar-block__title">Image de couverture</p>
                            <div class="image-wrapper-edit mb-2">
                                <img id="preview-img"
                                    src="/assets/img/default-article.jpg"
                                    alt="Aperçu"
                                    class="editable-image">
                                <label for="form-image-file" class="edit-icon" title="Choisir une image">
                                    <i class="fa-solid fa-pencil"></i>
                                </label>
                            </div>
                            <p id="img-feedback" class="text-success" style="display:none; font-size:var(--text-xs);">
                                <i class="fa-solid fa-check"></i> Image sélectionnée
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="sidebar-block">
                            <p class="sidebar-block__title">Publication</p>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary btn-sm"
                                    style="flex:1; justify-content:center; padding:12px;"
                                    onclick="submitCreate()">
                                    <i class="fa-solid fa-paper-plane"></i> Publier
                                </button>
                                <a href="/?page=home" class="btn btn-secondary btn-sm"
                                    style="flex:1; justify-content:center; padding:12px;">
                                    <i class="fa-solid fa-xmark"></i> Annuler
                                </a>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </form>

    </div>
</section>

<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
    const quill = new Quill('#quill-editor', {
        theme: 'snow'
    });

    <?php if (!empty($_POST['content'])): ?>
        quill.root.innerHTML = <?= json_encode($_POST['content']) ?>;
    <?php endif; ?>

    function previewImage(input) {
        if (!input.files[0]) return;
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('img-feedback').style.display = '';
        };
        reader.readAsDataURL(input.files[0]);
    }

    function submitCreate() {
        const title = document.getElementById('create-title').value.trim();
        if (!title) {
            showToast('Le titre ne peut pas être vide.', 'danger');
            return;
        }
        document.getElementById('form-title').value = title;
        document.getElementById('form-content').value = quill.root.innerHTML;
        document.getElementById('create-form').submit();
    }
</script>