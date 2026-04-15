<?php
if (!Auth::isAdmin()) {
    header('Location: /?page=home');
    exit;
}

$api      = new ApiClient();
$response = $api->get('/admin/users', Auth::getToken());
$users    = $response['body']['data'] ?? [];

// Flash toasts
if (!empty($_SESSION['admin_success'])) {
    $_SESSION['flash_toasts'][] = ['type' => 'success', 'msg' => $_SESSION['admin_success']];
    unset($_SESSION['admin_success']);
}
if (!empty($_SESSION['admin_error'])) {
    $_SESSION['flash_toasts'][] = ['type' => 'danger',  'msg' => $_SESSION['admin_error']];
    unset($_SESSION['admin_error']);
}
?>

<div class="admin-layout">
    <div class="container">

        <!-- En-tête -->
        <div class="admin-header">
            <i class="fa-solid fa-shield-halved"></i>
            <h1 class="admin-header__title">Gestion des utilisateurs</h1>
            <span class="admin-header__count"><?= count($users) ?> utilisateur<?= count($users) > 1 ? 's' : '' ?></span>
        </div>

        <!-- Table -->
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nom d'utilisateur</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="font-medium"><?= htmlspecialchars($u['username']) ?></td>
                            <td>
                                <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-secondary' ?>">
                                    <?= htmlspecialchars($u['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge bg-danger">Admin</span>
                                <?php elseif ($u['is_banned']): ?>
                                    <span class="badge bg-warning">Banni</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Actif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['role'] !== 'admin'): ?>
                                    <div class="admin-table__actions">

                                        <?php if (!$u['is_banned']): ?>
                                            <!-- Bouton Bannir -->
                                            <button class="btn btn-warning btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#banModal"
                                                data-userid="<?= $u['id'] ?>"
                                                data-username="<?= htmlspecialchars($u['username']) ?>">
                                                <i class="fa-solid fa-ban"></i> Bannir
                                            </button>

                                        <?php else: ?>
                                            <!-- Bouton Débannir (ouvre modal avec détails) -->
                                            <button class="btn btn-success btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#unbanModal"
                                                data-userid="<?= $u['id'] ?>"
                                                data-username="<?= htmlspecialchars($u['username']) ?>"
                                                data-reason="<?= htmlspecialchars($u['ban_reason'] ?? '') ?>"
                                                data-enddate="<?= htmlspecialchars($u['ban_end_date'] ?? '') ?>">
                                                <i class="fa-solid fa-circle-check"></i> Débannir
                                            </button>
                                        <?php endif; ?>

                                    </div>
                                <?php else: ?>
                                    <span class="text-muted"><i class="fa-solid fa-minus"></i></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!--  Modal Ban  -->
<div class="modal fade" id="banModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/?page=admin_ban">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-ban" style="color:var(--color-warning);"></i>
                        Bannir <span id="banUsername"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="banUserId">
                    <div class="mb-3">
                        <label class="form-label" for="banReason">Raison du ban</label>
                        <textarea id="banReason" name="reason" class="form-control" rows="3"
                            placeholder="Décrivez la raison…" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="banEndDate">
                            Date de fin
                            <span class="text-muted" style="font-weight:normal;">— laisser vide pour définitif</span>
                        </label>
                        <input type="date" id="banEndDate" name="end_date" class="form-control"
                            min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fa-solid fa-ban"></i> Confirmer le ban
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!--  Modal Débannir  -->
<div class="modal fade" id="unbanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/?page=admin_unban">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-circle-check" style="color:var(--color-success);"></i>
                        Débannir <span id="unbanUsername"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="unbanUserId">

                    <!-- Détails du ban actif -->
                    <div class="sidebar-block" style="margin-bottom:var(--space-4);">
                        <p class="sidebar-block__title">Détails du ban actif</p>
                        <div class="sidebar-block__row">
                            <span class="sidebar-block__label">Raison</span>
                            <span class="sidebar-block__value" id="unbanReason" style="max-width:220px; word-break:break-word;"></span>
                        </div>
                        <div class="sidebar-block__row">
                            <span class="sidebar-block__label">Expire le</span>
                            <span class="sidebar-block__value" id="unbanEndDate"></span>
                        </div>
                    </div>

                    <p style="font-size:var(--text-sm); color:var(--color-text-muted);">
                        Confirmer le débanissement de cet utilisateur ? Il pourra de nouveau accéder au site.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-circle-check"></i> Confirmer le débanissement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('banModal').addEventListener('show.bs.modal', e => {
        const btn = e.relatedTarget;
        document.getElementById('banUserId').value = btn.dataset.userid;
        document.getElementById('banUsername').textContent = btn.dataset.username;
        document.getElementById('banReason').value = '';
        document.getElementById('banEndDate').value = '';
    });

    document.getElementById('unbanModal').addEventListener('show.bs.modal', e => {
        const btn = e.relatedTarget;
        document.getElementById('unbanUserId').value = btn.dataset.userid;
        document.getElementById('unbanUsername').textContent = btn.dataset.username;
        document.getElementById('unbanReason').textContent = btn.dataset.reason || '—';
        document.getElementById('unbanEndDate').textContent = btn.dataset.enddate || 'Définitif';
    });
</script>