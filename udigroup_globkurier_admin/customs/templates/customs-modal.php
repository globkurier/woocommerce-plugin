<?php

namespace udigroup_globkurier;

if (! defined('ABSPATH')) {
	exit;
}

?>
<div id="globkurier-customs-modal-overlay" class="globkurier-customs-modal-overlay" style="display:none;">
	<div class="globkurier-customs-modal" role="dialog" aria-modal="true" aria-labelledby="globkurier-customs-modal-title">
		<div class="globkurier-customs-modal__header">
			<h2 id="globkurier-customs-modal-title" class="globkurier-customs-modal__title">
				<?php echo esc_html__('Uzupełnij deklarację celną', 'globkurier'); ?>
			</h2>
			<button type="button" class="globkurier-customs-modal__close" id="globkurier-customs-modal-close" aria-label="<?php echo esc_attr__('Zamknij', 'globkurier'); ?>">&times;</button>
		</div>

		<div class="globkurier-customs-modal__body" id="globkurier-customs-modal-body">
		</div>

		<div class="globkurier-customs-modal__footer">
			<p class="globkurier-customs-modal__error" id="globkurier-customs-modal-error" style="display:none;"></p>
			<button type="button" class="button button-primary globkurier-customs-modal__save" id="globkurier-customs-modal-save">
				<?php echo esc_html__('Zapisz i zamknij', 'globkurier'); ?>
			</button>
		</div>
	</div>
</div>
