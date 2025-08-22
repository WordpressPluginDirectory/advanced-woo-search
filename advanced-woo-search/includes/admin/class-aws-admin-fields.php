<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWS_Admin_Fields' ) ) :

    /**
     * Class for plugin admin ajax hooks
     */
    class AWS_Admin_Fields {

        /**
         * @var AWS_Admin_Fields The array of options that is need to be generated
         */
        private $options_array;

        /**
         * @var AWS_Admin_Fields Current plugin instance options
         */
        private $plugin_options;

        private $tab_name;

        /*
         * Constructor
         */
        public function __construct( $tab_name ) {

            $options = AWS_Admin_Options::options_array( $tab_name );
            $this->options_array = $options[$tab_name];
            $this->plugin_options = AWS_Admin_Options::get_settings();
            $this->tab_name = $tab_name;

            $this->generate_fields();

        }

        /*
         * Generate options fields
         */
        private function generate_fields() {

            if ( empty( $this->options_array ) ) {
                return;
            }

            $plugin_options = $this->plugin_options;

            echo '<div data-tab="'. esc_attr( $this->tab_name  ) .'">';

            echo '<table class="form-table">';
            echo '<tbody>';

            foreach ( $this->options_array as $k => $value ) {

                $opt_value = '';
                if ( isset( $value['id'] ) && isset( $plugin_options[$value['id']] ) ) {
                    $opt_value = $plugin_options[$value['id']];
                } elseif ( isset( $value['value'] ) ) {
                    $opt_value = $value['value'];
                }

                $disabled = isset( $value['disabled'] ) && $value['disabled'] ? 'disabled' : '';
                $disabled_row_class = $disabled ? ' class="aws-disabled"' : '';

                switch ( $value['type'] ) {

                    case 'text': ?>
                        <tr<?php echo $disabled_row_class; ?>>
                            <th scope="row">
                                <?php echo wp_kses_post( $value['name'] ); ?>
                                <?php if ( isset( $value['tip'] ) && $value['tip'] ) { ?>
                                    <span class="aws-help-tip aws-tip" data-tip="<?php echo esc_attr( $value['tip'] ); ?>"></span>
                                <?php } ?>
                            </th>
                            <td>
                                <input <?php echo $disabled; ?> type="text" name="<?php echo esc_attr( $value['id'] ); ?>" class="regular-text" value="<?php echo esc_attr( stripslashes( $opt_value ) ); ?>">
                                <p class="description"><?php echo wp_kses_post( $value['desc'] ); ?></p>
                            </td>
                        </tr>
                        <?php break;

                    case 'image': ?>
                        <tr<?php echo $disabled_row_class; ?>>
                            <th scope="row">
                                <?php echo wp_kses_post( $value['name'] ); ?>
                                <?php if ( isset( $value['tip'] ) && $value['tip'] ) { ?>
                                    <span class="aws-help-tip aws-tip" data-tip="<?php echo esc_attr( $value['tip'] ); ?>"></span>
                                <?php } ?>
                            </th>
                            <td>
                                <input <?php echo $disabled; ?> type="text" name="<?php echo esc_attr( $value['id'] ); ?>" class="regular-text" value="<?php echo esc_attr( stripslashes( $opt_value ) ); ?>">
                                <p class="description"><?php echo wp_kses_post( $value['desc'] ); ?></p>
                                <img style="display: block;max-width: 100px;margin-top: 20px;" src="<?php echo esc_url( $opt_value ); ?>">
                            </td>
                        </tr>
                        <?php break;

                    case 'number': ?>

                        <?php
                        $params = '';
                        $params .= isset( $value['step'] ) ? ' step="' . $value['step'] . '"' : '';
                        $params .= isset( $value['min'] ) ? ' min="' . $value['min'] . '"' : '';
                        $params .= isset( $value['max'] ) ? ' max="' . $value['max'] . '"' : '';
                        ?>

                        <tr<?php echo $disabled_row_class; ?>>
                            <th scope="row">
                                <?php echo wp_kses_post( $value['name'] ); ?>
                                <?php if ( isset( $value['tip'] ) && $value['tip'] ) { ?>
                                    <span class="aws-help-tip aws-tip" data-tip="<?php echo esc_attr( $value['tip'] ); ?>"></span>
                                <?php } ?>
                            </th>
                            <td>
                                <input <?php echo $disabled; ?> type="number" <?php echo $params; ?> name="<?php echo esc_attr( $value['id'] ); ?>" class="regular-text" value="<?php echo esc_attr( stripslashes($opt_value ) ); ?>">
                                <p class="description"><?php echo wp_kses_post( $value['desc'] ); ?></p>
                            </td>
                        </tr>
                        <?php break;

                    case 'textarea': ?>
                        <tr<?php echo $disabled_row_class; ?>>
                            <th scope="row">
                                <?php echo wp_kses_post( $value['name'] ); ?>
                                <?php if ( isset( $value['tip'] ) && $value['tip'] ) { ?>
                                    <span class="aws-help-tip aws-tip" data-tip="<?php echo esc_attr( $value['tip'] ); ?>"></span>
                                <?php } ?>
                            </th>
                            <td>
                                <?php $textarea_cols = isset( $value['cols'] ) ? $value['cols'] : "55"; ?>
                                <?php $textarea_rows = isset( $value['rows'] ) ? $value['rows'] : "4"; ?>
                                <?php $textarea_output = isset( $value['allow_tags'] ) ? wp_kses( $opt_value, AWS_Helpers::get_kses( $value['allow_tags'] ) ) : stripslashes( $opt_value ); ?>
                                <textarea <?php echo $disabled; ?> id="<?php echo esc_attr( $value['id'] ); ?>" name="<?php echo esc_attr( $value['id'] ); ?>" cols="<?php echo $textarea_cols; ?>" rows="<?php echo $textarea_rows; ?>"><?php print $textarea_output; ?></textarea>
                                <p class="description"><?php echo wp_kses_post( $value['desc'] ); ?></p>
                            </td>
                        </tr>
                        <?php break;

                    case 'checkbox': ?>
                        <tr<?php echo $disabled_row_class; ?>>
                            <th scope="row">
                                <?php echo wp_kses_post( $value['name'] ); ?>
                                <?php if ( isset( $value['tip'] ) && $value['tip'] ) { ?>
                                    <span class="aws-help-tip aws-tip" data-tip="<?php echo esc_attr( $value['tip'] ); ?>"></span>
                                <?php } ?>
                            </th>
                            <td>
                                <?php $checkbox_options = $opt_value; ?>
                                <?php foreach ( $value['choices'] as $val => $label ) { ?>
                                    <div class="aws-input-checkbox">
                                        <input <?php echo $disabled; ?> type="checkbox" name="<?php echo esc_attr( $value['id'] . '[' . $val . ']' ); ?>" id="<?php echo esc_attr( $value['id'] . '_' . $val ); ?>" value="1" <?php checked( $checkbox_options[$val], '1' ); ?>> <label for="<?php echo esc_attr( $value['id'] . '_' . $val ); ?>"><?php echo esc_html( $label ); ?></label><br>
                                    </div>
                                <?php } ?>
                                <p class="description"><?php echo wp_kses_post( $value['desc'] ); ?></p>
                            </td>
                        </tr>
                        <?php break;

                    case 'radio': ?>
                        <tr<?php echo $disabled_row_class; ?>>
                            <th scope="row">
                                <?php echo wp_kses_post( $value['name'] ); ?>
                                <?php if ( isset( $value['tip'] ) && $value['tip'] ) { ?>
                                    <span class="aws-help-tip aws-tip" data-tip="<?php echo esc_attr( $value['tip'] ); ?>"></span>
                                <?php } ?>
                            </th>
                            <td>
                                <?php foreach ( $value['choices'] as $val => $label ) { ?>
                                    <div class="aws-input-radio">
                                        <input <?php echo $disabled; ?> class="radio" type="radio" name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'].$val ); ?>" value="<?php echo esc_attr( $val ); ?>" <?php checked( $opt_value, $val ); ?>> <label for="<?php echo esc_attr( $value['id'].$val ); ?>"><?php echo wp_kses_post( $label ); ?></label><br>
                                    </div>
                                <?php } ?>
                                <p class="description"><?php echo wp_kses_post( $value['desc'] ); ?></p>
                            </td>
                        </tr>
                        <?php break;

                    case 'select': ?>
                        <tr<?php echo $disabled_row_class; ?>>
                            <th scope="row">
                                <?php echo wp_kses_post( $value['name'] ); ?>
                                <?php if ( isset( $value['tip'] ) && $value['tip'] ) { ?>
                                    <span class="aws-help-tip aws-tip" data-tip="<?php echo esc_attr( $value['tip'] ); ?>"></span>
                                <?php } ?>
                            </th>
                            <td>
                                <select <?php echo $disabled; ?> name="<?php echo esc_attr( $value['id'] ); ?>">
                                    <?php foreach ( $value['choices'] as $val => $label ) { ?>
                                        <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $opt_value, $val ); ?>><?php echo esc_html( $label ); ?></option>
                                    <?php } ?>
                                </select>
                                <p class="description"><?php echo wp_kses_post( $value['desc'] ); ?></p>
                            </td>
                        </tr>
                        <?php break;

                    case 'toggler': ?>

                        <?php $active = $opt_value == 'true' ? ' checked="checked"' : ''; ?>

                        <tr<?php echo $disabled_row_class; ?>>
                            <th scope="row">
                                <?php echo wp_kses_post( $value['name'] ); ?>
                                <?php if ( isset( $value['tip'] ) && $value['tip'] ) { ?>
                                    <span class="aws-help-tip aws-tip" data-tip="<?php echo esc_attr( $value['tip'] ); ?>"></span>
                                <?php } ?>
                            </th>
                            <td>
                                <label class="aws-toggle-label aws-toggler-field">
                                    <input <?php echo $disabled; ?> class="aws-toggler" type="checkbox" name="<?php echo esc_attr( $value['id'] ); ?>" <?php echo $active; ?>>
                                </label>
                                <p class="description"><?php echo wp_kses_post( $value['desc'] ); ?></p>
                            </td>
                        </tr>

                        <?php break;

                    case 'select_advanced': ?>
                        <tr<?php echo $disabled_row_class; ?>>
                            <th scope="row">
                                <?php echo wp_kses_post( $value['name'] ); ?>
                                <?php if ( isset( $value['tip'] ) && $value['tip'] ) { ?>
                                    <span class="aws-help-tip aws-tip" data-tip="<?php echo esc_attr( $value['tip'] ); ?>"></span>
                                <?php } ?>
                            </th>
                            <td>
                                <select <?php echo $disabled; ?> name="<?php echo esc_attr( $value['id'].'[]' ); ?>" multiple class="chosen-select">
                                    <?php $values = $opt_value; ?>
                                    <?php foreach ( $value['choices'] as $val => $label ) {  ?>
                                        <?php $selected = ( is_array( $values ) && in_array( $val, $values ) ) ? ' selected="selected" ' : ''; ?>
                                        <option value="<?php echo esc_attr( $val ); ?>"<?php echo $selected; ?>><?php echo esc_html( $label ); ?></option>
                                    <?php } ?>
                                </select>
                                <p class="description"><?php echo wp_kses_post( $value['desc'] ); ?></p>

                                <?php if ( $value['sub_option'] ): ?>
                                    <?php $sub_options = $value['sub_option']; ?>
                                    <br><br>
                                    <p>
                                        <label for="<?php echo esc_attr( $sub_options['id'] ); ?>">
                                            <input type="checkbox" value="1" id="<?php echo esc_attr( $sub_options['id'] ); ?>" name="<?php echo esc_attr( $sub_options['id'] ); ?>" <?php checked( $plugin_options[ $sub_options['id'] ], '1' ); ?>>
                                            <?php echo esc_html( $sub_options['desc'] ); ?>
                                        </label>
                                    </p>
                                <?php endif; ?>

                            </td>
                        </tr>
                        <?php break;

                    case 'radio-image': ?>
                        <tr<?php echo $disabled_row_class; ?>>
                            <th scope="row">
                                <?php echo wp_kses_post( $value['name'] ); ?>
                                <?php if ( isset( $value['tip'] ) && $value['tip'] ) { ?>
                                    <span class="aws-help-tip aws-tip" data-tip="<?php echo esc_attr( $value['tip'] ); ?>"></span>
                                <?php } ?>
                            </th>
                            <td>
                                <ul class="img-select">
                                    <?php foreach ( $value['choices'] as $val => $img ) { ?>
                                        <?php
                                        $style = isset( $value['styles'] ) &&  isset( $value['styles'][$val] ) ? 'style="' . $value['styles'][$val] . ';"' : '';
                                        ?>
                                        <li class="option" <?php echo $style; ?>>
                                            <input <?php echo $disabled; ?> class="radio" type="radio" name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'].$val ); ?>" value="<?php echo esc_attr( $val ); ?>" <?php checked( $opt_value, $val ); ?>>
                                            <span class="ico" style="background: url('<?php echo esc_url( AWS_URL . 'assets/img/' . $img ); ?>') no-repeat 50% 50%;"></span>
                                        </li>
                                    <?php } ?>
                                </ul>
                                <p class="description"><?php echo wp_kses_post( $value['desc'] ); ?></p>
                            </td>
                        </tr>
                        <?php break;

                    case 'sortable': ?>
                        <tr<?php echo $disabled_row_class; ?>>
                            <th scope="row">
                                <?php echo wp_kses_post( $value['name'] ); ?>
                                <?php if ( isset( $value['tip'] ) && $value['tip'] ) { ?>
                                    <span class="aws-help-tip aws-tip" data-tip="<?php echo esc_attr( $value['tip'] ); ?>"></span>
                                <?php } ?>
                            </th>
                            <td>

                                <script>
                                    jQuery(document).ready(function() {

                                        jQuery( "#sti-sortable1, #sti-sortable2" ).sortable({
                                            connectWith: ".connectedSortable",
                                            placeholder: "highlight",
                                            update: function(event, ui){
                                                var serviceList = '';
                                                jQuery("#sti-sortable2 li").each(function(){

                                                    serviceList = serviceList + ',' + jQuery(this).attr('id');

                                                });
                                                var serviceListOut = serviceList.substring(1);
                                                jQuery('#<?php echo esc_attr( $value['id'] ); ?>').attr('value', serviceListOut);
                                            }
                                        }).disableSelection();

                                    });
                                </script>

                                <p class="description"><?php echo wp_kses_post( $value['desc'] ); ?></p><br><br>

                                <?php
                                $all_buttons = $value['choices'];
                                $active_buttons = explode( ',', $opt_value );
                                $inactive_buttons = array_diff($all_buttons, $active_buttons);
                                ?>

                                <div class="sortable-container">

                                    <div class="sortable-title">
                                        <?php esc_html_e( 'Active sources', 'advanced-woo-search' ) ?><br>
                                        <?php esc_html_e( 'Change order by drag&drop', 'advanced-woo-search' ) ?>
                                    </div>

                                    <ul id="sti-sortable2" class="sti-sortable enabled connectedSortable">
                                        <?php
                                        if ( count( $active_buttons ) > 0 ) {
                                            foreach ($active_buttons as $button) {
                                                if ( ! $button ) continue;
                                                echo '<li id="' . esc_attr( $button ) . '" class="sti-btn sti-' . esc_attr( $button ) . '-btn">' . $button . '</li>';
                                            }
                                        }
                                        ?>
                                    </ul>

                                </div>

                                <div class="sortable-container">

                                    <div class="sortable-title">
                                        <?php esc_html_e( 'Deactivated sources', 'advanced-woo-search' ) ?><br>
                                        <?php esc_html_e( 'Excluded from search results', 'advanced-woo-search' ) ?>
                                    </div>

                                    <ul id="sti-sortable1" class="sti-sortable disabled connectedSortable">
                                        <?php
                                        if ( count( $inactive_buttons ) > 0 ) {
                                            foreach ($inactive_buttons as $button) {
                                                echo '<li id="' . esc_attr( $button ) . '" class="sti-btn sti-' . esc_attr( $button ) . '-btn">' . $button . '</li>';
                                            }
                                        }
                                        ?>
                                    </ul>

                                </div>

                                <input type="hidden" id="<?php echo esc_attr( $value['id'] ); ?>" name="<?php echo esc_attr( $value['id'] ); ?>" value="<?php echo esc_attr( $opt_value ); ?>" />

                            </td>
                        </tr>
                        <?php break;

                    case 'table': ?>

                        <?php
                        $table_head = isset( $value['table_head'] ) && $value['table_head'] ? $value['table_head'] : '';
                        ?>

                        <tr<?php echo $disabled_row_class; ?>>

                            <th scope="row">
                                <?php echo wp_kses_post( $value['name'] ); ?>
                                <?php if ( isset( $value['tip'] ) && $value['tip'] ) { ?>
                                    <span class="aws-help-tip aws-tip" data-tip="<?php echo esc_attr( $value['tip'] ); ?>"></span>
                                <?php } ?>
                            </th>

                            <td>

                                <p class="description"><?php echo wp_kses_post( $value['desc'] ); ?></p>

                                <table class="aws-table aws-table-sources widefat" cellspacing="0">

                                    <thead>
                                    <tr>
                                        <th class="aws-name"><?php echo esc_html( $table_head ); ?></th>
                                        <th class="aws-actions"></th>
                                        <th class="aws-active"></th>
                                    </tr>
                                    </thead>

                                    <tbody>

                                    <?php $table_options = isset( $plugin_options[ $value['id'] ] ) ? $plugin_options[ $value['id'] ] : array(); ?>

                                    <?php if ( is_array( $table_options ) ) { ?>

                                        <?php foreach ( $value['choices'] as $val => $fchoices ) { ?>

                                            <?php
                                            $active_class = isset( $table_options[$val] ) && $table_options[$val] ? 'active' : '';
                                            $name_class = '';
                                            $label = is_array( $fchoices ) ? $fchoices['label'] : $fchoices;
                                            if ( strpos( $label, 'index disabled' ) !== false ) {
                                                $active_class = 'disabled';
                                            }
                                            if ( strpos( $val, ':disabled' ) !== false ) {
                                                $active_class = 'disabled';
                                                $name_class = ' aws-disabled';
                                            }
                                            ?>

                                            <tr>
                                                <td class="aws-name<?php echo $name_class; ?>"><?php echo $label; ?></td>
                                                <td class="aws-actions"></td>
                                                <td class="aws-active <?php echo $active_class; ?>">
                                                    <span data-change-state="1" data-setting="<?php echo esc_attr( $value['id'] ); ?>" data-name="<?php echo esc_attr( $val ); ?>" class="aws-yes" title="<?php echo esc_attr__( 'Disable this source', 'advanced-woo-search' ); ?>"><?php echo esc_html__( 'Yes', 'advanced-woo-search' ); ?></span>
                                                    <span data-change-state="0" data-setting="<?php echo esc_attr( $value['id'] ); ?>" data-name="<?php echo esc_attr( $val ); ?>" class="aws-no" title="<?php echo esc_attr__( 'Enable this source', 'advanced-woo-search' ); ?>"><?php echo esc_html__( 'No', 'advanced-woo-search' ); ?></span>
                                                    <span style="display: none;" class="aws-disabled" title="<?php echo esc_attr__( 'Source index disabled', 'advanced-woo-search' ); ?>"><?php echo esc_html__( 'No', 'advanced-woo-search' ); ?></span>
                                                </td>
                                            </tr>
                                        <?php } ?>

                                    <?php } ?>

                                    </tbody>

                                </table>

                            </td>

                        </tr>

                        <?php break;

                    case 'heading':

                        $heading_tag = isset( $value['heading_type'] ) && $value['heading_type'] === 'text' ? 'span' : 'h3';
                        $heading_description = isset( $value['desc'] ) ? $value['desc'] : '';
                        $id = isset($value['id']) && $value['id'] ? 'id="' . $value['id'] . '"' : '';
                        ?>

                        <tr<?php echo $disabled_row_class; ?>>
                            <th scope="row">
                                <<?php echo $heading_tag; ?> <?php echo $id; ?> class="aws-heading">
                                    <?php echo wp_kses_post( $value['name'] ); ?>
                                    <?php if ( isset( $value['tip'] ) && $value['tip'] ) { ?>
                                        <span class="aws-help-tip aws-tip" data-tip="<?php echo esc_attr( $value['tip'] ); ?>"></span>
                                    <?php } ?>
                                </<?php echo $heading_tag; ?>>
                            </th>

                            <?php if ( $heading_description ): ?>
                                <td>
                                    <p class="description"><?php echo $heading_description; ?></p>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php break;

                    case 'html':
                        $custom_html = isset( $value['html'] ) ? $value['html'] : '';
                        $description = isset( $value['desc'] ) ? $value['desc'] : '';
                        ?>
                        <tr<?php echo $disabled_row_class; ?>>
                            <th scope="row">
                                <?php echo wp_kses_post( $value['name'] ); ?>
                                <?php if ( isset( $value['tip'] ) && $value['tip'] ) { ?>
                                    <span class="aws-help-tip aws-tip" data-tip="<?php echo esc_attr( $value['tip'] ); ?>"></span>
                                <?php } ?>
                            </th>
                            <td>
                                <?php echo $value['html']; ?>
                                <?php if ( $description ): ?>
                                    <p class="description"><?php echo $description; ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <?php break;

                }

            }

            echo '</tbody>';
            echo '</table>';

            echo '<p class="submit"><input name="Submit" type="submit" class="button-primary" value="' . esc_attr__( 'Save Changes', 'advanced-woo-search' ) . '" /></p>';

            echo '</div>';

        }



    }

endif;
