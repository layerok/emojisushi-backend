<!-- Template for new file -->
<script type="text/template" id="<?= $this->getId('template') ?>">
    <div class="upload-object upload-object-image dz-preview dz-file-preview <?= isset($modeMulti) ? 'mode-multi' : '' ?>">
        <?php if (isset($modeMulti)): ?>
            <div class="form-check">
                <input
                    class="form-check-input"
                    data-record-selector
                    type="checkbox"
                    value=""
                />
            </div>
            <div class="drag-handle">
                <i class="icon-list-reorder"></i>
            </div>
        <?php endif ?>

        <div class="file-data-container">
            <div class="file-data-container-inner">
                <div class="icon-container image">
                    <img data-dz-thumbnail style="<?= $cssDimensions ?>" alt="" />
                </div>
                <div class="info">
                    <h4 class="filename">
                        <span data-dz-name></span>
                    </h4>
                    <p class="description" data-description></p>
                    <p class="size" data-dz-size></p>
                </div>
                <div class="meta">
                    <div class="progress-bar"><span class="upload-progress" data-dz-uploadprogress></span></div>
                    <div class="error-message"><span data-dz-errormessage></span></div>
                </div>
            </div>
        </div>
    </div>
</script>
