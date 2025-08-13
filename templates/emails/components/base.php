<?php
/**
 * Base email template.
 *
 * @var string $template_file Path of the specific email content template.
 * @var array  $booking       Booking data array.
 * @var array  $customer      Customer data array.
 * @var string $payment_button Optional HTML for the payment button.
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
</head>
<body style="font-family: Arial, sans-serif; line-height:1.6; color:#333; margin:0; padding:20px;">
    <div style="max-width:600px; margin:0 auto;">
        <div style="background:#2563eb; color:#ffffff; padding:20px; text-align:center;">
            <h1>Costabilerent</h1>
        </div>
        <div style="background:#ffffff; padding:20px; border:1px solid #e2e8f0;">
            <?php include $template_file; ?>
            <?php if (! empty($payment_button)) { ?>
                <div style="text-align:center; margin-top:20px;">
                    <?php echo $payment_button; ?>
                </div>
            <?php } ?>
        </div>
        <div style="background:#f1f5f9; padding:15px; text-align:center; font-size:12px; color:#64748b;">
            <?php
            printf(
                __('Powered by %s', 'custom-rental-manager'),
                '<a href="https://totaliweb.com">Totaliweb</a>'
            );
            ?>
        </div>
    </div>
</body>
</html>
