<!DOCTYPE html>
<html>
<head>
    <title>Project Invitation</title>
</head>
<body>
    <h1>Hello, {{ $inviteeName }}</h1>
    <p>You have been invited to join the project: {{ $projectName }} by {{ $inviterName }}.</p>
    <p>Click the link below to accept the invitation:</p>
    <a href="{{ $acceptLink }}">Accept Invitation</a>
</body>
</html>