<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome to ArthaPredict</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; color: #334155;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); overflow: hidden;">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #2563eb, #1d4ed8); padding: 32px; text-align: center;">
            <div style="width: 64px; height: 64px; background-color: #ffffff; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                <img src="{{ \App\Models\AppSetting::getLogoUrl() }}" alt="Logo" style="width: 48px; height: 48px;">
            </div>
            <h1 style="color: #ffffff; font-size: 28px; font-weight: 700; margin: 0;">Welcome to {{ \App\Models\AppSetting::getAppName() }}!</h1>
        </div>

        <!-- Body -->
        <div style="padding: 32px;">
            <h2 style="font-size: 24px; font-weight: 600; color: #1e293b; margin: 0 0 16px 0;">Hello {{ $user->name }},</h2>

            <p style="font-size: 16px; line-height: 1.6; color: #64748b; margin: 0 0 20px 0;">
                Thank you for joining <strong>ArthaPredict</strong>! Your account has been successfully created. You're now part of a community that's revolutionizing stock analysis with AI-powered insights.
            </p>

            <p style="font-size: 16px; line-height: 1.6; color: #64748b; margin: 0 0 24px 0;">
                Here's what you can do with your new account:
            </p>

            <ul style="font-size: 15px; color: #64748b; margin: 0 0 24px 0; padding-left: 20px;">
                <li style="margin-bottom: 8px;">📊 Analyze stocks with AI-powered predictions</li>
                <li style="margin-bottom: 8px;">📈 View real-time market data and trends</li>
                <li style="margin-bottom: 8px;">⭐ Add stocks to your personalized watchlist</li>
                <li style="margin-bottom: 8px;">📝 Share and discover community insights</li>
            </ul>

            <div style="text-align: center; margin: 32px 0;">
                <a href="{{ route('dashboard') }}" style="display: inline-block; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 16px; transition: opacity 0.2s;">
                    Start Exploring
                </a>
            </div>

            <div style="background-color: #f1f5f9; border-radius: 8px; padding: 20px; margin-top: 24px;">
                <p style="font-size: 14px; color: #475569; margin: 0;">
                    <strong>Security Tip:</strong> If you didn't create this account or have questions, please contact our support team or reply to this email.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div style="background-color: #f8fafc; padding: 24px; text-align: center; border-top: 1px solid #e2e8f0;">
            <p style="font-size: 13px; color: #94a3b8; margin: 0;">
                © {{ date('Y') }} ArthaPredict. All rights reserved.
            </p>
            <p style="font-size: 12px; color: #cbd5e1; margin: 8px 0 0 0;">
                AI-Based Stock Analysis & Prediction System
            </p>
        </div>
    </div>
</body>
</html>
