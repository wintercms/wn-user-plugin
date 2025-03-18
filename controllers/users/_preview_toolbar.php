<a
    href="<?= Backend::url('winter/user/users') ?>"
    class="btn btn-default oc-icon-chevron-left">
    <?= e(trans('winter.user::lang.groups.return_to_users')) ?>
</a>
<a
    href="<?= Backend::url('winter/user/users/update/'.$formModel->id) ?>"
    class="btn btn-primary oc-icon-pencil">
    <?= e(trans('winter.user::lang.users.update_details')) ?>
</a>
<?php if ($this->user->hasAccess('winter.users.impersonate_user')): ?>
    <a
        href="javascript:;"
        data-request="onImpersonateUser"
        data-request-confirm="<?= e(trans('winter.user::lang.users.impersonate_confirm')) ?>"
        class="btn btn-default oc-icon-user-secret">
        <?= e(trans('winter.user::lang.users.impersonate_user')) ?>
    </a>
<?php endif ?>

<?php if ($formModel->isSuspended()): ?>
    <a
        href="javascript:;"
        data-request="onUnsuspendUser"
        data-request-confirm="<?= e(trans('winter.user::lang.users.unsuspend_confirm')) ?>"
        class="btn btn-default oc-icon-unlock-alt">
        <?= e(trans('winter.user::lang.users.unsuspend')) ?>
    </a>
<?php endif ?>

<?php
/* @todo
<div class="btn-group">
    <a
        href="<?= Backend::url('winter/user/users/update/'.$formModel->id) ?>"
        class="btn btn-default oc-icon-pencil">
        Deactivate
    </a>
    <a
        href="<?= Backend::url('winter/user/users/update/'.$formModel->id) ?>"
        class="btn btn-default oc-icon-pencil">
        Ban user
    </a>
    <a
        href="<?= Backend::url('winter/user/users/update/'.$formModel->id) ?>"
        class="btn btn-default oc-icon-pencil">
        Delete
    </a>
</div>
*/
?>

<?=
    /**
     * @event winter.user.view.extendPreviewToolbar
     * Fires when preview user toolbar is rendered.
     *
     * Example usage:
     *
     *     Event::listen('winter.user.view.extendPreviewToolbar', function (
     *         (Winter\User\Controllers\Users) $controller,
     *         (Winter\User\Models\User) $record
     *     ) {
     *         return $controller->makePartial('~/path/to/partial');
     *     });
     *
     */
    $this->fireViewEvent('winter.user.view.extendPreviewToolbar', [
        'record' => $formModel
    ]);
?>
