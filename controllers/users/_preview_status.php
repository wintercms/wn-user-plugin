        <?php if ($formModel->is_guest): ?>
            <?= $this->makePartial('hint_guest') ?>
        <?php elseif ($formModel->isBanned()): ?>
            <?= $this->makePartial('hint_banned') ?>
        <?php elseif ($formModel->trashed()): ?>
            <?= $this->makePartial('hint_trashed') ?>
        <?php elseif (!$formModel->is_activated): ?>
            <?= $this->makePartial('hint_activate') ?>
        <?php endif ?>
