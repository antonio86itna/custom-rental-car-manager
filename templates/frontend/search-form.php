<?php
/**
 * Frontend Search Form Template - REDESIGNED
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$locations = crcm_get_locations();
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
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo $location->term_id; ?>">
                                    <?php echo esc_html($location->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="crcm-field-group">
                        <label for="return_location"><?php _e('Sede di riconsegna', 'custom-rental-manager'); ?></label>
                        <select id="return_location" name="return_location">
                            <option value=""><?php _e('Ischia Porto', 'custom-rental-manager'); ?></option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo $location->term_id; ?>">
                                    <?php echo esc_html($location->name); ?>
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

<style>
.crcm-search-container {
    max-width: 800px;
    margin: 40px auto;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 0;
    box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
    overflow: hidden;
}

.crcm-search-wrapper {
    background: white;
    margin: 3px;
    border-radius: 17px;
    padding: 40px 35px;
}

.crcm-search-header {
    text-align: center;
    margin-bottom: 35px;
}

.crcm-search-header h2 {
    font-size: 28px;
    font-weight: 700;
    color: #2c3e50;
    margin: 0 0 10px 0;
    line-height: 1.3;
}

.crcm-search-header p {
    font-size: 16px;
    color: #7f8c8d;
    margin: 0;
}

.crcm-search-form {
    max-width: 100%;
}

.crcm-pagination {
    text-align: center;
    margin: 20px 0;
}

.crcm-pagination a {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 4px;
    background: #667eea;
    color: #fff;
    border-radius: 4px;
    text-decoration: none;
}

.crcm-pagination a.active {
    background: #764ba2;
}

.crcm-field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 25px;
}

.crcm-field-group {
    display: flex;
    flex-direction: column;
}

.crcm-field-group label {
    font-weight: 600;
    color: #34495e;
    margin-bottom: 8px;
    font-size: 14px;
}

.crcm-input-wrapper {
    position: relative;
}

.crcm-input-wrapper input,
.crcm-field-group select {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e1e8ed;
    border-radius: 12px;
    font-size: 15px;
    transition: all 0.3s ease;
    background: #fafbfc;
    box-sizing: border-box;
}

.crcm-input-wrapper input:focus,
.crcm-field-group select:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.crcm-input-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    font-size: 16px;
}

.crcm-checkbox-group {
    display: flex;
    align-items: center;
    padding: 15px 0;
    grid-column: 1 / -1;
}

.crcm-checkbox-group input[type="checkbox"] {
    display: none;
}

.crcm-checkbox-group label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-weight: 500;
    color: #34495e;
    margin: 0;
}

.crcm-checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #e1e8ed;
    border-radius: 4px;
    margin-right: 12px;
    position: relative;
    transition: all 0.3s ease;
    background: white;
    flex-shrink: 0;
}

.crcm-checkbox-group input[type="checkbox"]:checked + label .crcm-checkbox-custom {
    background: #667eea;
    border-color: #667eea;
}

.crcm-checkbox-group input[type="checkbox"]:checked + label .crcm-checkbox-custom::after {
    content: '✓';
    position: absolute;
    color: white;
    font-size: 12px;
    font-weight: bold;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.crcm-advanced-toggle {
    text-align: center;
    margin-bottom: 25px;
}

.crcm-toggle-btn {
    background: none;
    border: none;
    color: #667eea;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 auto;
    transition: all 0.3s ease;
}

.crcm-toggle-btn:hover {
    color: #764ba2;
}

.crcm-toggle-icon {
    transition: transform 0.3s ease;
}

.crcm-toggle-btn.active .crcm-toggle-icon {
    transform: rotate(180deg);
}

.crcm-search-actions {
    text-align: center;
}

.crcm-search-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 16px 40px;
    border-radius: 50px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.crcm-search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.5);
}

.crcm-search-btn:active {
    transform: translateY(0);
}

.crcm-search-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.crcm-btn-icon {
    font-size: 18px;
}

.crcm-results-container {
    margin-top: 40px;
    padding: 0 20px;
}

.crcm-results-content {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .crcm-search-container {
        margin: 20px 15px;
        border-radius: 15px;
    }
    
    .crcm-search-wrapper {
        padding: 25px 20px;
    }
    
    .crcm-search-header h2 {
        font-size: 24px;
    }
    
    .crcm-field-row {
        grid-template-columns: 1fr;
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .crcm-search-btn {
        width: 100%;
        padding: 18px 30px;
        font-size: 15px;
    }
}

/* Form Animations */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.crcm-time-fields {
    animation: slideIn 0.3s ease;
}

/* Loading States */
.crcm-search-btn.loading {
    position: relative;
    overflow: hidden;
}

.crcm-search-btn.loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% { left: -100%; }
    100% { left: 100%; }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Advanced options toggle
    $('#advanced-options-toggle').on('click', function() {
        const $timeFields = $('.crcm-time-fields');
        const $button = $(this);
        
        if ($timeFields.is(':visible')) {
            $timeFields.slideUp(300);
            $button.removeClass('active');
        } else {
            $timeFields.slideDown(300);
            $button.addClass('active');
        }
    });
    
    // Auto-sync return location with pickup location
    $('#pickup_location').on('change', function() {
        const selectedValue = $(this).val();
        const selectedText = $(this).find('option:selected').text();
        
        if (selectedValue) {
            $('#return_location').val(selectedValue);
        }
    });
    
    // Set minimum dates
    const today = new Date().toISOString().split('T')[0];
    $('#pickup_date, #return_date').attr('min', today);
    
    // Auto-set return date when pickup date changes
    $('#pickup_date').on('change', function() {
        const pickupDate = new Date($(this).val());
        const returnDate = new Date(pickupDate);
        returnDate.setDate(returnDate.getDate() + 1);
        
        const returnDateString = returnDate.toISOString().split('T')[0];
        $('#return_date').attr('min', returnDateString);
        
        if (!$('#return_date').val()) {
            $('#return_date').val(returnDateString);
        }
    });
});
</script>
