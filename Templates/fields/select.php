<p>
    <label><?php echo $field['label']; ?></label>
    <select name="<?php echo $fieldKey; ?>" id="<?php echo $fieldKey; ?>">
        <?php foreach ($field['options'] as $item): ?>
            <option value="<?php echo $item; ?>" <?php echo ($item == $field['value']) ? 'selected' : ''; ?>>
                <?php echo $item; ?>
            </option>
        <?php endforeach; ?>
    </select>
</p>
