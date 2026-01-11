<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; background-color: #f7f9fc; color: #333; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .logo { font-size: 24px; font-weight: bold; color: #3b82f6; margin-bottom: 20px; display: inline-block; }
        .code { font-family: monospace; font-size: 32px; letter-spacing: 2px; background: #f3f4f6; padding: 15px; border-radius: 8px; text-align: center; margin: 20px 0; font-weight: bold; }
        .btn { display: inline-block; background-color: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 10px; }
        .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">FlowKosmo</div>
        <h1>You've been invited!</h1>
        <p>You have been invited to join the FlowKosmo beta program.</p>
        <p>To set up your workspace, use the following invite code:</p>
        
        <div class="code">{{ $code }}</div>

        <p>Click the button below to get started automatically:</p>
        
        <div style="text-align: center;">
            <a href="{{ $url }}" class="btn">Setup Workspace</a>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} FlowKosmo. All rights reserved.
        </div>
    </div>
</body>
</html>
