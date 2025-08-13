<?php
/**
 * Frontend Search Form Template - REDESIGNED
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="crcm-search-container">
    <div class="crcm-search-wrapper">
        <div class="crcm-search-header">
            <h2><?php _e('Noleggia un\'auto o scooter a Ischia', 'custom-rental-manager'); ?></h2>
            <p><?php _e('Trova il veicolo perfetto per la tua vacanza in pochi click.', 'custom-rental-manager'); ?></p>
        </div>
        
        <form id="crcm-vehicle-search" class="crcm-search-form" data-per-page="6">
            <div class="crcm-search-fields">
                <!-- Date Fields Row -->
                <div class="crcm-field-row">
                    <div class="crcm-field-group">
                        <label for="pickup_date"><?php _e('Data di ritiro', 'custom-rental-manager'); ?></label>
                        <div class="crcm-input-wrapper">
                            <input type="date" id="pickup_date" name="pickup_date" required />
                            <span class="crcm-input-icon">📅</span>
                        </div>
                    </div>
                    
                    <div class="crcm-field-group">
                        <label for="return_date"><?php _e('Data di riconsegna', 'custom-rental-manager'); ?></label>
                        <div class="crcm-input-wrapper">
                            <input type="date" id="return_date" name="return_date" required />
                            <span class="crcm-input-icon">📅</span>
                        </div>
                    </div>
                </div>
                
                <!-- Time Fields Row (Hidden by default, expandable) -->
                <div class="crcm-field-row crcm-time-fields" style="display: none;">
                    <div class="crcm-field-group">
                        <label for="pickup_time"><?php _e('Orario ritiro', 'custom-rental-manager'); ?></label>
                        <select id="pickup_time" name="pickup_time">
                            <option value="08:00">08:00</option>
                            <option value="09:00" selected>09:00</option>
                            <option value="10:00">10:00</option>
                            <option value="11:00">11:00</option>
                            <option value="12:00">12:00</option>
                            <option value="13:00">13:00</option>
                            <option value="14:00">14:00</option>
                            <option value="15:00">15:00</option>
                            <option value="16:00">16:00</option>
                            <option value="17:00">17:00</option>
                            <option value="18:00">18:00</option>
                            <option value="19:00">19:00</option>
                        </select>
                    </div>
                    
                    <div class="crcm-field-group">
                        <label for="return_time"><?php _e('Orario riconsegna', 'custom-rental-manager'); ?></label>
                        <select id="return_time" name="return_time">
                            <option value="08:00">08:00</option>
                            <option value="09:00">09:00</option>
                            <option value="10:00">10:00</option>
                            <option value="11:00">11:00</option>
                            <option value="12:00">12:00</option>
                            <option value="13:00">13:00</option>
                            <option value="14:00">14:00</option>
                            <option value="15:00">15:00</option>
                            <option value="16:00">16:00</option>
                            <option value="17:00">17:00</option>
                            <option value="18:00" selected>18:00</option>
                            <option value="19:00">19:00</option>
                        </select>
                    </div>
                </div>
                
                <!-- Location Fields Row -->
                <div class="crcm-field-row">
                    <div class="crcm-field-group">
                        <label for="pickup_location"><?php _e('Sede di ritiro', 'custom-rental-manager'); ?></label>
                        <select id="pickup_location" name="pickup_location">
                            <option value=""><?php _e('Ischia Porto', 'custom-rental-manager'); ?></option>
                            <?php foreach ( $locations as $location ) : ?>
                                <option value="<?php echo esc_attr( $location['id'] ); ?>">
                                    <?php echo esc_html( $location['name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="crcm-field-group">
                        <label for="return_location"><?php _e('Sede di riconsegna', 'custom-rental-manager'); ?></label>
                        <select id="return_location" name="return_location">
                            <option value=""><?php _e('Ischia Porto', 'custom-rental-manager'); ?></option>
                            <?php foreach ( $locations as $location ) : ?>
                                <option value="<?php echo esc_attr( $location['id'] ); ?>">
                                    <?php echo esc_html( $location['name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Home Delivery Option -->
                <div class="crcm-field-row">
                    <div class="crcm-checkbox-group">
                        <input type="checkbox" id="home_delivery" name="home_delivery" value="1" />
                        <label for="home_delivery">
                            <span class="crcm-checkbox-custom"></span>
                            <?php _e('Richiedi consegna a domicilio (gratuita)', 'custom-rental-manager'); ?>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Advanced Options Toggle -->
            <div class="crcm-advanced-toggle">
                <button type="button" class="crcm-toggle-btn" id="advanced-options-toggle">
                    <span><?php _e('Opzioni avanzate', 'custom-rental-manager'); ?></span>
                    <span class="crcm-toggle-icon">▼</span>
                </button>
            </div>
            
            <!-- Search Button -->
            <div class="crcm-search-actions">
                <button type="submit" class="crcm-search-btn">
                    <span class="crcm-btn-icon">🔍</span>
                    <?php _e('Cerca Disponibilità', 'custom-rental-manager'); ?>
                </button>
            </div>
            
            <?php wp_nonce_field('crcm_nonce', 'crcm_nonce'); ?>
        </form>
    </div>
</div>

<!-- Search Results Container -->
<div id="crcm-search-results" class="crcm-results-container" style="display: none;">
    <div id="crcm-results-content" class="crcm-results-content">
        <!-- Results will be loaded here via AJAX -->
    </div>
    <div id="crcm-pagination" class="crcm-pagination"></div>
</div>

