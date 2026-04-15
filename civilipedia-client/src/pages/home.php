<?php
$api      = new ApiClient();
$response = $api->get('/articles');
$articles = $response['body']['data'] ?? [];
?>

<!-- Hero -->
<header class="page-hero">
    <div class="page-hero__content">
        <h1>Civilipédia</h1>
        <p>Votre encyclopédie des grandes civilisations du monde.</p>
    </div>
</header>

<!-- Contenu -->
<section class="home-section container">

    <div class="home-toolbar">
        <div class="home-toolbar__search search-wrapper position-relative">
            <input class="form-control" type="search" id="homeSearchInput"
                placeholder="Rechercher une civilisation…" autocomplete="off">
            <ul id="homeSearchResults" class="list-group d-none"></ul>
        </div>

        <?php if (Auth::isLoggedIn()): ?>
            <a href="/?page=article_create" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Nouvel article
            </a>
        <?php endif; ?>
    </div>

    <p class="home-section__heading" id="home-heading">Tous les articles</p>

    <?php if (empty($articles)): ?>
        <div class="home-empty">
            <i class="fa-regular fa-file-lines"></i>
            <p>Aucun article publié pour le moment.</p>
        </div>
    <?php else: ?>
        <div class="articles-grid" id="articles-grid">
            <?php foreach ($articles as $article):
                $imgSrc = !empty($article['image_url']) ? $article['image_url'] : '/assets/img/default-article.jpg';

                $currentUser = Auth::getUser();
                $canDelete   = Auth::isLoggedIn() && (
                    Auth::isAdmin() ||
                    ($currentUser && $currentUser['username'] === ($article['author'] ?? ''))
                );

                $date = !empty($article['updated_at']) ? $article['updated_at'] : ($article['created_at'] ?? '');
            ?>
                <div class="article-card"
                    id="article-card-<?= $article['id'] ?>"
                    role="link" tabindex="0"
                    onclick="window.location='/?page=article&id=<?= $article['id'] ?>'"
                    onkeydown="if(event.key==='Enter')window.location='/?page=article&id=<?= $article['id'] ?>'"
                    data-content="<?= htmlspecialchars($article['content'] ?? '') ?>">

                    <div class="card-img-top-custom">
                        <img src="<?= htmlspecialchars($imgSrc) ?>"
                            alt="<?= htmlspecialchars($article['title']) ?>"
                            loading="lazy">

                        <?php if ($canDelete): ?>
                            <button type="button"
                                class="delete-badge"
                                title="Supprimer l'article"
                                onclick="event.stopPropagation(); openDeleteModal(<?= $article['id'] ?>, this)">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="card-body-custom">
                        <h3><?= htmlspecialchars($article['title']) ?></h3>
                        <span class="author"><?= htmlspecialchars($article['author'] ?? '') ?></span>
                        <span class="date"><?= $date ? date('d/m/Y', strtotime($date)) : '' ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</section>

<!-- Modale de confirmation de suppression -->
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
                    Êtes-vous sûr de vouloir supprimer cet article ? Il sera définitivement effacé et ne pourra pas être restauré.
                </p>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--color-border); padding:var(--space-4) var(--space-6); gap:var(--space-2);">
                <button type="button" class="btn btn-secondary"
                    style="padding:10px 16px;"
                    data-bs-dismiss="modal">
                    Annuler
                </button>
                <button type="button" class="btn btn-danger" id="deleteModalConfirm"
                    style="padding:10px 16px; justify-content:center;">
                    <i class="fa-solid fa-trash"></i> Supprimer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    //  Modale de suppression 
    let _pendingDeleteId = null;
    let _pendingDeleteBtn = null;

    function openDeleteModal(id, btn) {
        _pendingDeleteId = id;
        _pendingDeleteBtn = btn;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }

    document.getElementById('deleteModalConfirm').addEventListener('click', async function() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
        modal.hide();

        const id = _pendingDeleteId;
        const btn = _pendingDeleteBtn;
        if (!id) return;

        if (btn) btn.disabled = true;

        try {
            const res = await fetch('/?page=article_delete_ajax&id=' + id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'id=' + id,
            });

            if (res.ok || res.redirected) {
                const card = document.getElementById('article-card-' + id);
                if (card) {
                    card.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => card.remove(), 280);
                }
                showToast('Article supprimé.', 'success');
            } else {
                if (btn) btn.disabled = false;
                showToast('Erreur lors de la suppression.', 'danger');
            }
        } catch (e) {
            if (btn) btn.disabled = false;
            showToast('Erreur réseau.', 'danger');
        }

        _pendingDeleteId = null;
        _pendingDeleteBtn = null;
    });

    //  Recherche AJAX home 
    (function() {
        const input = document.getElementById('homeSearchInput');
        const resList = document.getElementById('homeSearchResults');
        const grid = document.getElementById('articles-grid');
        const heading = document.getElementById('home-heading');

        if (!input || !grid) return;

        const allCards = Array.from(grid.children);

        input.addEventListener('input', function() {
            const q = this.value.trim().toLowerCase();
            resList.innerHTML = '';

            if (q.length < 2) {
                resList.classList.add('d-none');
                allCards.forEach(c => c.style.display = '');
                heading.textContent = 'Tous les articles';
                return;
            }

            const stripHtml = html => html ? html.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim() : '';
            const matches = allCards.filter(card => {
                const title = card.querySelector('h3')?.textContent.toLowerCase() ?? '';
                const rawContent = card.dataset.content ?? '';
                const content = stripHtml(rawContent).toLowerCase();
                return title.includes(q) || content.includes(q);
            });

            matches.slice(0, 6).forEach(card => {
                const li = document.createElement('li');
                li.className = 'list-group-item';
                li.textContent = card.querySelector('h3')?.textContent ?? '';
                li.addEventListener('click', () => {
                    resList.classList.add('d-none');
                    input.value = '';
                    card.click();
                });
                resList.appendChild(li);
            });
            resList.classList.toggle('d-none', matches.length === 0);

            allCards.forEach(card => {
                const title = card.querySelector('h3')?.textContent.toLowerCase() ?? '';
                const cardContent = stripHtml(card.dataset.content ?? '').toLowerCase();
                card.style.display = (title.includes(q) || cardContent.includes(q)) ? '' : 'none';
            });

            heading.textContent = matches.length ?
                'Résultats pour « ' + this.value.trim() + ' »' :
                'Aucun article ne correspond à votre recherche.';
        });

        document.addEventListener('click', e => {
            if (!e.target.closest('.home-toolbar__search')) {
                resList.classList.add('d-none');
            }
        });
    })();
</script>