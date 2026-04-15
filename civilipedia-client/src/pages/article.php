<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: /?page=home');
    exit;
}

$api      = new ApiClient();
$response = $api->get('/articles/' . $id);
if ($response['status'] !== 200) {
    header('Location: /?page=home');
    exit;
}

$article = $response['body'];
$user    = Auth::getUser();

// Tout utilisateur connecté peut modifier un article
$canEdit = Auth::isLoggedIn();

$imageUrl = !empty($article['image_url'])
    ? $article['image_url'] . '?t=' . time()
    : '/assets/img/default-article.jpg';

// Convertit une date UTC (venant de l'API) en heure Europe/Paris
function toParisDate(string $utcDate, string $format = 'd/m/Y'): string
{
    $dt = new DateTime($utcDate, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone('Europe/Paris'));
    return $dt->format($format);
}

$editError  = '';
$imageError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'edit') {
    if (!$canEdit) {
        header('Location: /?page=home');
        exit;
    }

    $newTitle   = trim($_POST['title']   ?? '');
    $newContent =      $_POST['content'] ?? '';

    if ($newTitle === '' || $newContent === '') {
        $editError = 'Le titre et le contenu sont requis.';
    } else {
        $put = $api->put('/articles/' . $id, [
            'title'   => $newTitle,
            'content' => $newContent,
        ], Auth::getToken());

        if ($put['status'] === 200) {
            $fileError = $_FILES['article_image']['error'] ?? UPLOAD_ERR_NO_FILE;

            if ($fileError === UPLOAD_ERR_OK) {
                $up = $api->uploadFile(
                    '/articles/' . $id . '/image',
                    $_FILES['article_image']['tmp_name'],
                    $_FILES['article_image']['type'],
                    'image',
                    Auth::getToken()
                );
                if ($up['status'] !== 200) {
                    $imageError = 'Image non mise à jour : ' . ($up['body']['error']['message'] ?? 'Erreur API.');
                }
            } elseif ($fileError !== UPLOAD_ERR_NO_FILE) {
                $phpErrors = [
                    UPLOAD_ERR_INI_SIZE   => 'Fichier trop grand (upload_max_filesize).',
                    UPLOAD_ERR_FORM_SIZE  => 'Fichier trop grand (post_max_size).',
                    UPLOAD_ERR_PARTIAL    => 'Upload partiel, réessayez.',
                    UPLOAD_ERR_CANT_WRITE => 'PHP ne peut pas écrire sur le disque.',
                ];
                $imageError = $phpErrors[$fileError] ?? 'Erreur upload PHP (code ' . $fileError . ').';
            }

            if (empty($imageError)) {
                header('Location: /?page=article&id=' . $id);
                exit;
            }
        } else {
            $editError = $put['body']['error']['message'] ?? 'Erreur lors de la modification.';
        }
    }

    $article['title']   = $newTitle   ?: $article['title'];
    $article['content'] = $newContent ?: $article['content'];
}

$historyResponse = $api->get('/articles/' . $id . '/history');
$history         = $historyResponse['body']['data'] ?? [];
$hasHistory      = count($history) > 0;

// Pré-formater les dates en Paris pour le JS (évite le bug UTC dans la modal)
$historyForJs = array_values(array_map(function ($v) {
    return array_merge($v, [
        'created_at_display' => toParisDate($v['created_at'], 'd/m/Y à H:i'),
    ]);
}, $history));

$startInEditMode = ($editError !== '' || $imageError !== '');
?>

<?php if ($editError): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($editError) ?>, 'danger'));
    </script>
<?php endif; ?>
<?php if ($imageError): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($imageError) ?>, 'warning'));
    </script>
<?php endif; ?>

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<section class="article-layout">
    <div class="container">

        <form id="edit-form" method="POST" action="/?page=article&id=<?= $id ?>" enctype="multipart/form-data">
            <input type="hidden" name="_action" value="edit">
            <input type="hidden" name="title" id="form-title">
            <input type="hidden" name="content" id="form-content">

            <div class="row g-5">

                <!--  Colonne principale (8/12)  -->
                <div class="col-lg-8 order-last order-lg-first">

                    <!-- Titre -->
                    <div id="view-title" class="mb-3">
                        <h1 class="article-title"><?= htmlspecialchars($article['title']) ?></h1>
                    </div>
                    <div id="edit-title" style="display:none;" class="mb-3">
                        <input type="text" id="input-title" class="create-title-input"
                            value="<?= htmlspecialchars($article['title']) ?>">
                    </div>

                    <!-- Méta -->
                    <div class="article-meta mb-5">
                        <span class="article-meta__item">
                            <i class="fa-regular fa-user"></i>
                            <?= htmlspecialchars($article['author']) ?>
                        </span>
                        <span class="article-meta__item">
                            <i class="fa-regular fa-calendar"></i>
                            <?= toParisDate($article['created_at']) ?>
                        </span>
                        <?php if (!empty($article['updated_at']) && $article['updated_at'] !== $article['created_at']): ?>
                            <span class="article-meta__item">
                                <i class="fa-solid fa-rotate"></i>
                                <?= toParisDate($article['updated_at']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Contenu -->
                    <div id="view-content" class="article-content">
                        <?= $article['content'] ?>
                    </div>
                    <div id="edit-content" style="display:none;">
                        <div id="quill-editor"></div>
                    </div>

                    <!-- Historique -->
                    <?php if ($hasHistory): ?>
                        <div class="history-section" id="historique">
                            <p class="history-section__title">Historique des modifications</p>
                            <div class="admin-table-wrap">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Modifié par</th>
                                            <th>Date</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($history as $i => $v): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($v['modified_by'] ?? '—') ?></td>
                                                <td class="text-muted"><?= toParisDate($v['created_at'], 'd/m/Y H:i') ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-outline-dark btn-sm"
                                                        onclick="showVersion(<?= $i ?>)">
                                                        <i class="fa-solid fa-eye"></i> Voir
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

                <!--  Sidebar (4/12)  -->
                <div class="col-lg-4 order-first order-lg-last">
                    <div class="article-sidebar">

                        <!-- Boutons de navigation -->
                        <div class="sidebar-block">
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" id="btn-read"
                                    class="btn btn-sm btn-primary"
                                    style="padding:12px;"
                                    onclick="switchMode('read')">
                                    <i class="fa-solid fa-eye"></i> Lire
                                </button>

                                <?php if ($canEdit): ?>
                                    <button type="button" id="btn-edit"
                                        class="btn btn-sm btn-outline-dark"
                                        style="padding:12px;"
                                        onclick="switchMode('edit')">
                                        <i class="fa-solid fa-pen"></i> Modifier
                                    </button>
                                <?php endif; ?>

                                <?php if ($hasHistory): ?>
                                    <a href="#historique" id="btn-history"
                                        class="btn btn-sm btn-outline-dark ms-auto"
                                        style="padding:12px;">
                                        <i class="fa-solid fa-clock-rotate-left"></i> Historique
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Actions édition -->
                        <?php if ($canEdit): ?>
                            <div class="sidebar-block" id="edit-actions-block" style="display:none;">
                                <p class="sidebar-block__title">Modifications</p>
                                <div class="d-flex gap-2 mb-2">
                                    <button type="button" class="btn btn-primary btn-sm"
                                        style="flex:1; justify-content:center; padding:12px;"
                                        onclick="submitEdit()">
                                        <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm"
                                        style="flex:1; justify-content:center; padding:12px;"
                                        onclick="switchMode('read')">
                                        <i class="fa-solid fa-xmark"></i> Annuler
                                    </button>
                                </div>
                                <!-- Ouvre la modale au lieu de soumettre directement -->
                                <button type="button"
                                    class="btn btn-danger btn-sm w-100"
                                    style="justify-content:center; padding:12px;"
                                    onclick="openDeleteModal()">
                                    <i class="fa-solid fa-trash"></i> Supprimer l'article
                                </button>
                            </div>
                        <?php endif; ?>

                        <!-- Image -->
                        <div class="sidebar-block">
                            <p class="sidebar-block__title">Image</p>

                            <div id="view-image">
                                <img id="article-img"
                                    src="<?= htmlspecialchars($imageUrl) ?>"
                                    alt=""
                                    style="width:100%;border-radius:var(--radius-md);object-fit:cover;aspect-ratio:4/3;">
                            </div>

                            <?php if ($canEdit): ?>
                                <div id="edit-image" class="image-wrapper-edit" style="display:none;">
                                    <img id="article-img-edit"
                                        src="<?= htmlspecialchars($imageUrl) ?>"
                                        alt="" class="editable-image">
                                    <label for="article-image-input" class="edit-icon" title="Changer l'image">
                                        <i class="fa-solid fa-pencil"></i>
                                    </label>
                                    <input type="file" name="article_image" id="article-image-input"
                                        accept="image/jpeg,image/png,image/webp,image/gif"
                                        class="d-none" onchange="previewImage(this)">
                                </div>
                                <p id="img-feedback" style="display:none;font-size:var(--text-xs);margin-top:var(--space-2);color:var(--color-success);">
                                    <i class="fa-solid fa-check"></i> Nouvelle image sélectionnée
                                </p>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

            </div>
        </form>

        <?php if ($canEdit): ?>
            <!-- Formulaire de suppression — soumis par la modale, sans onsubmit confirm() -->
            <form id="delete-form" method="POST" action="/?page=article_delete">
                <input type="hidden" name="id" value="<?= $article['id'] ?>">
            </form>
        <?php endif; ?>

    </div>
</section>

<!-- Modale de confirmation de suppression -->
<?php if ($canEdit): ?>
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
            <div class="modal-content">
                <div class="modal-header" style="border-bottom:1px solid var(--color-border); padding:var(--space-5) var(--space-6);">
                    <div style="display:flex; align-items:center; gap:var(--space-3);">
                        <div style="width:36px;height:36px;border-radius:var(--radius);background:var(--color-danger-bg);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fa-solid fa-trash" style="color:var(--color-danger);font-size:0.85rem;"></i>
                        </div>
                        <div>
                            <h5 class="modal-title" id="deleteModalLabel"
                                style="font-size:var(--text-base);font-weight:var(--weight-semibold);color:var(--color-text);margin:0;">
                                Supprimer l'article
                            </h5>
                            <p style="font-size:var(--text-xs);color:var(--color-text-muted);margin:0;">
                                Cette action est irréversible.
                            </p>
                        </div>
                    </div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body" style="padding:var(--space-5) var(--space-6);">
                    <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin:0;">
                        Êtes-vous sûr de vouloir supprimer
                        <strong style="color:var(--color-text);"><?= htmlspecialchars($article['title']) ?></strong> ?
                        Il sera définitivement effacé et ne pourra pas être restauré.
                    </p>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--color-border); padding:var(--space-4) var(--space-6); gap:var(--space-2);">
                    <button type="button" class="btn btn-secondary"
                        style="padding:10px 16px;"
                        data-bs-dismiss="modal">
                        Annuler
                    </button>
                    <button type="button" class="btn btn-danger"
                        style="padding:10px 16px; justify-content:center;"
                        onclick="document.getElementById('delete-form').submit()">
                        <i class="fa-solid fa-trash"></i> Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Modal version historique -->
<?php if ($hasHistory): ?>
    <div class="modal fade" id="versionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Version du <span id="v-date"></span>
                        <small class="text-muted ms-2">par <span id="v-author"></span></small>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <img src="<?= htmlspecialchars($imageUrl) ?>"
                        alt=""
                        style="width:100%;border-radius:var(--radius-md);object-fit:cover;aspect-ratio:16/7;margin-bottom:var(--space-5);display:block;">

                    <h2 id="v-title-text"
                        style="font-size:var(--text-xl);font-weight:var(--weight-bold);color:var(--color-text);margin-bottom:var(--space-5);line-height:1.3;">
                    </h2>

                    <div id="v-content" class="article-content"
                        style="max-height:420px;overflow-y:auto;border-top:1px solid var(--color-border);padding-top:var(--space-4);">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const _versions = <?= json_encode($historyForJs) ?>;
        const _currentTitle = <?= json_encode($article['title']) ?>;

        function showVersion(index) {
            const v = _versions[index];
            document.getElementById('v-date').textContent = v.created_at_display ?? v.created_at;
            document.getElementById('v-author').textContent = v.modified_by ?? '—';
            document.getElementById('v-title-text').textContent = v.title || _currentTitle;
            document.getElementById('v-content').innerHTML = v.content ?? '';
            new bootstrap.Modal(document.getElementById('versionModal')).show();
        }
    </script>
<?php endif; ?>

<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
    let quill = null;

    function openDeleteModal() {
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }

    function initQuill() {
        if (quill) return;
        quill = new Quill('#quill-editor', {
            theme: 'snow'
        });
        quill.root.innerHTML = <?= json_encode($article['content']) ?>;
    }

    function switchMode(mode) {
        const isEdit = mode === 'edit';

        document.getElementById('view-title').style.display = isEdit ? 'none' : '';
        document.getElementById('edit-title').style.display = isEdit ? '' : 'none';
        document.getElementById('view-content').style.display = isEdit ? 'none' : '';
        document.getElementById('edit-content').style.display = isEdit ? '' : 'none';
        document.getElementById('view-image').style.display = isEdit ? 'none' : '';

        const editImg = document.getElementById('edit-image');
        if (editImg) editImg.style.display = isEdit ? '' : 'none';

        const editActions = document.getElementById('edit-actions-block');
        if (editActions) editActions.style.display = isEdit ? '' : 'none';

        const btnRead = document.getElementById('btn-read');
        const btnEdit = document.getElementById('btn-edit');
        if (btnRead) {
            btnRead.classList.toggle('btn-primary', !isEdit);
            btnRead.classList.toggle('btn-outline-dark', isEdit);
        }
        if (btnEdit) {
            btnEdit.classList.toggle('btn-primary', isEdit);
            btnEdit.classList.toggle('btn-outline-dark', !isEdit);
        }

        const btnHistory = document.getElementById('btn-history');
        const historySection = document.getElementById('historique');
        if (btnHistory) btnHistory.style.display = isEdit ? 'none' : '';
        if (historySection) historySection.style.display = isEdit ? 'none' : '';

        if (isEdit) initQuill();
    }

    function previewImage(input) {
        if (!input.files[0]) return;
        const reader = new FileReader();
        reader.onload = e => {
            ['article-img', 'article-img-edit'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.src = e.target.result;
            });
            const fb = document.getElementById('img-feedback');
            if (fb) fb.style.display = '';
        };
        reader.readAsDataURL(input.files[0]);
    }

    function submitEdit() {
        if (!quill) return;
        const title = document.getElementById('input-title').value.trim();
        if (!title) {
            showToast('Le titre ne peut pas être vide.', 'danger');
            return;
        }
        document.getElementById('form-title').value = title;
        document.getElementById('form-content').value = quill.root.innerHTML;
        document.getElementById('edit-form').submit();
    }

    <?php if ($startInEditMode): ?>
        document.addEventListener('DOMContentLoaded', () => switchMode('edit'));
    <?php endif; ?>
</script>