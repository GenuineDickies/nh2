<?php

use App\Core\View;
use App\Models\CatalogItem;

$isServices = $mode === 'services';
$old = $old ?? [];
$errors = $errors ?? [];
$action = $item
    ? (($isServices ? '/catalog/services/' : '/catalog/items/') . (int) $item['id'])
    : ($isServices ? '/catalog/services' : '/catalog/items');
$cancel = $isServices ? '/catalog/services' : '/catalog/items';
$allowedTypes = $isServices ? ['service', 'labor', 'fee'] : ['part', 'material'];
?>
<form class="form-grid" method="post" action="<?= View::e($action) ?>" novalidate>
    <?php if ($errors): ?>
        <div class="alert">Please fix the highlighted fields.</div>
    <?php endif; ?>

    <div class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Catalog</p>
                <h2><?= $item ? 'Edit Item' : ($isServices ? 'New Service' : 'New Item') ?></h2>
            </div>
            <?php if ($item): ?>
                <span class="status-badge <?= $item['status'] === 'inactive' ? 'status-muted' : '' ?>"><?= View::e($item['status']) ?></span>
            <?php endif; ?>
        </div>

        <div class="fields two">
            <label>Name
                <input name="name" value="<?= View::e($old['name'] ?? '') ?>" required>
                <?php if (isset($errors['name'])): ?><small class="field-error"><?= View::e($errors['name']) ?></small><?php endif; ?>
            </label>
            <label>Part #
                <input name="sku" value="<?= View::e($old['sku'] ?? '') ?>" required>
                <?php if (isset($errors['sku'])): ?><small class="field-error"><?= View::e($errors['sku']) ?></small><?php endif; ?>
            </label>
            <label>Type
                <select name="item_type">
                    <?php foreach ($allowedTypes as $type): ?>
                        <option value="<?= View::e($type) ?>" <?= ($old['item_type'] ?? '') === $type ? 'selected' : '' ?>><?= View::e(ucwords($type)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['item_type'])): ?><small class="field-error"><?= View::e($errors['item_type']) ?></small><?php endif; ?>
            </label>
            <label>Category
                <input name="category" value="<?= View::e($old['category'] ?? '') ?>" required>
                <?php if (isset($errors['category'])): ?><small class="field-error"><?= View::e($errors['category']) ?></small><?php endif; ?>
            </label>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Pricing</p>
                <h2>Estimate Defaults</h2>
            </div>
        </div>
        <div class="fields four">
            <label>Price
                <input name="price" inputmode="decimal" value="<?= View::e((string) ($old['price'] ?? '0.00')) ?>" required>
                <?php if (isset($errors['price'])): ?><small class="field-error"><?= View::e($errors['price']) ?></small><?php endif; ?>
            </label>
            <label>Price type
                <select name="price_type">
                    <?php foreach (CatalogItem::PRICE_TYPES as $priceType): ?>
                        <option value="<?= View::e($priceType) ?>" <?= ($old['price_type'] ?? 'flat_rate') === $priceType ? 'selected' : '' ?>><?= View::e(ucwords(str_replace('_', ' ', $priceType))) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['price_type'])): ?><small class="field-error"><?= View::e($errors['price_type']) ?></small><?php endif; ?>
            </label>
            <label>Taxable
                <select name="taxable">
                    <option value="0" <?= empty($old['taxable']) ? 'selected' : '' ?>>No</option>
                    <option value="1" <?= !empty($old['taxable']) ? 'selected' : '' ?>>Yes</option>
                </select>
            </label>
            <label>Status
                <select name="status">
                    <?php foreach (CatalogItem::STATUSES as $status): ?>
                        <option value="<?= View::e($status) ?>" <?= ($old['status'] ?? 'active') === $status ? 'selected' : '' ?>><?= View::e(ucwords($status)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['status'])): ?><small class="field-error"><?= View::e($errors['status']) ?></small><?php endif; ?>
            </label>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Description</p>
                <h2>Operator Notes</h2>
            </div>
        </div>
        <label>Short description
            <input name="short_description" value="<?= View::e($old['short_description'] ?? '') ?>">
        </label>
        <label>Long description
            <textarea name="long_description" rows="5"><?= View::e($old['long_description'] ?? '') ?></textarea>
        </label>
        <label>Warranty eligible
            <select name="warranty_eligible">
                <option value="0" <?= empty($old['warranty_eligible']) ? 'selected' : '' ?>>No</option>
                <option value="1" <?= !empty($old['warranty_eligible']) ? 'selected' : '' ?>>Yes</option>
            </select>
        </label>
    </div>

    <div class="sticky-actions">
        <a class="secondary-action" href="<?= View::e($cancel) ?>">Cancel</a>
        <button class="primary-action" type="submit"><?= $item ? 'Save Changes' : 'Create Item' ?></button>
    </div>
</form>

