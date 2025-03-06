<div class="form-group">
    <label class="switch">
        <input type="hidden" name="<?php echo $fieldKey; ?>" value="false">
        <input type="checkbox"
               name="<?php echo $fieldKey; ?>"
               value="true"
            <?php echo ($field['value'] == 'true' ? 'checked' : ''); ?>>
        <span class="slider"></span>
    </label>
    <span class="label-text"><?php echo ($field['label']); ?></span>
</div>
