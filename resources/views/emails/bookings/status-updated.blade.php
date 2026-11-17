<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Booking Status Update</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f5f7fa; padding: 30px; color: #333;">

    <div style="max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 10px;">

        <h2 style="color: #0f766e;">RoamNePura</h2>

        <p>Hello {{ $booking->name }},</p>

        @if ($booking->status === 'confirmed')
            <h2 style="color: #15803d;">Your booking is confirmed!</h2>

            <p>We're happy to let you know that your booking request has been accepted.</p>
        @else
            <h2 style="color: #dc2626;">Booking request update</h2>

            <p>Thank you for choosing RoamNePura. Unfortunately, we are unable to accept your booking request at this time.</p>

            <p>Please contact our team if you would like assistance with alternative travel arrangements.</p>
        @endif

        <h3>Booking Details</h3>

        <p><strong>Tour:</strong> {{ $booking->tour }}</p>
        <p><strong>Travel Date:</strong> {{ $booking->travel_date }}</p>
        <p><strong>Travelers:</strong> {{ $booking->travelers }}</p>
        <p><strong>Booking Status:</strong> {{ ucfirst($booking->status) }}</p>

        <p>Thank you for choosing RoamNePura!</p>

        <p>Best regards,<br>RoamNePura Team</p>

    </div>

</body>
</html>
