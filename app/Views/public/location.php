<?php

use App\Core\View;

$address = View::address($serviceRequest, 'Address not captured');
$customerName = $serviceRequest['first_name'] . ' ' . $serviceRequest['last_name'];
$values = $formData ?? [
    'location_address' => $serviceRequest['address_line_1'] ?? '',
    'location_city' => $serviceRequest['city'] ?? '',
    'location_state' => $serviceRequest['state'] ?? '',
    'location_postal_code' => $serviceRequest['postal_code'] ?? '',
];
?>
<article class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Location</p>
            <h2>Confirm where the technician should meet you</h2>
        </div>
    </div>
    <?php if ($flash !== null): ?>
        <div class="alert public-flash"><?= View::e($flash) ?></div>
    <?php endif; ?>
    <dl class="details">
        <dt>For</dt>
        <dd><?= View::e($customerName) ?></dd>
        <dt>Service</dt>
        <dd><?= View::e($serviceRequest['requested_service']) ?></dd>
        <dt>On file</dt>
        <dd><?= View::e($address) ?></dd>
    </dl>
</article>

<?php if ($token !== ''): ?>
    <form class="panel" method="post" action="/p/location/<?= View::e($token) ?>/confirm">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Looks right</p>
                <h2>Confirm this address</h2>
            </div>
        </div>
        <p class="muted">If the address above is where you want service, tap to confirm.</p>
        <div class="inline-actions">
            <button class="primary-action" type="submit">Confirm Address</button>
        </div>
    </form>

    <form class="panel" method="post" action="/p/location/<?= View::e($token) ?>/update">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Not quite</p>
                <h2>Update the address</h2>
            </div>
        </div>
        <label>Street
            <input name="location_address" value="<?= View::e($values['location_address'] ?? '') ?>" required>
            <?php if (isset($errors['location_address'])): ?><small class="field-error"><?= View::e($errors['location_address']) ?></small><?php endif; ?>
        </label>
        <div class="fields three">
            <label>City
                <input name="location_city" value="<?= View::e($values['location_city'] ?? '') ?>" required>
                <?php if (isset($errors['location_city'])): ?><small class="field-error"><?= View::e($errors['location_city']) ?></small><?php endif; ?>
            </label>
            <label>State
                <input name="location_state" value="<?= View::e($values['location_state'] ?? '') ?>">
            </label>
            <label>ZIP
                <input name="location_postal_code" value="<?= View::e($values['location_postal_code'] ?? '') ?>">
            </label>
        </div>
        <div class="inline-actions">
            <button class="secondary-action" type="submit">Update Address</button>
        </div>
    </form>
<?php endif; ?>
