<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .ticket-info {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .message {
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #0073aa;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #6c757d;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>New Support Ticket</h2>
    </div>

    <div class="ticket-info">
        <p><strong>Site:</strong> {{site_name}}</p>
        <p><strong>Ticket ID:</strong> #{{ticket_id}}</p>
        <p><strong>Subject:</strong> {{subject}}</p>
        <p><strong>From:</strong> {{user_name}}</p>
    </div>

    <div class="message">
        <h3>Message:</h3>
        <div>{{message}}</div>
    </div>

    <div style="text-align: center;">
        <a href="{{admin_url}}" class="button">View Ticket</a>
    </div>

    <div class="footer">
        <p>This is an automated message from {{site_name}}. Please do not reply to this email.</p>
    </div>
</body>
</html>