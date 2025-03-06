<!DOCTYPE html>
<html lang="en"> <!-- Changed language to English -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Form</title> <!-- Changed title to English -->
    <style>
        /* ... your existing CSS styles ... */
        :root {
            --primary-color: #4a6da7;
            --primary-hover: #3a5d97;
            --secondary-color: #28a745;
            --secondary-hover: #218838;
            --danger-color: #dc3545;
            --danger-hover: #c82333;
            --background: #f7f9fc;
            --text-color: #333;
            --border-color: #dde1e7;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --border-radius: 6px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background);
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 700px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid var(--border-color);
        }

        h1 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 30px;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }

        h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--primary-color);
            border-radius: 3px;
        }

        .error-message {
            background-color: #fff3f3;
            border-left: 4px solid var(--danger-color);
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #d63031;
        }

        .form-group {
            margin-bottom: 22px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 15px;
            transition: all 0.3s ease;
            background-color: #fcfcfc;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 109, 167, 0.2);
            background-color: #fff;
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        input[type="submit"] {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        input[type="submit"]:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        input[name="submitNext"],
        input[name="submitComplete"] {
            background-color: var(--secondary-color);
        }

        input[name="submitNext"]:hover,
        input[name="submitComplete"]:hover {
            background-color: var(--secondary-hover);
        }

        input[name="submitReset"] {
            background-color: var(--danger-color);
        }

        input[name="submitReset"]:hover {
            background-color: var(--danger-hover);
        }

        .form-group {
            display: flex; /* Flexbox aktivieren für die gesamte Gruppe */
            align-items: center; /* Vertikale Zentrierung */
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 60px; /* Breite des Switches */
            height: 34px; /* Höhe des Switches */
            margin-right: 10px; /* Abstand zwischen Switch und Label-Text */
        }

        .switch input {
            opacity: 0; /* Versteckt die Standard-Checkbox */
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc; /* Hintergrundfarbe des Switches */
            transition: .4s; /* Übergangseffekt */
            border-radius: 34px; /* Abgerundete Ecken */
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px; /* Höhe des Schiebereglers */
            width: 26px; /* Breite des Schiebereglers */
            left: 4px; /* Abstand vom linken Rand */
            bottom: 4px; /* Abstand vom unteren Rand */
            background-color: white; /* Hintergrundfarbe des Schiebereglers */
            transition: .4s; /* Übergangseffekt */
            border-radius: 50%; /* Runde Form */
        }

        /* Wenn die Checkbox aktiviert ist */
        input:checked + .slider {
            background-color: #4a6da7; /* Hintergrundfarbe, wenn aktiviert */
        }

        input:checked + .slider:before {
            transform: translateX(26px); /* Verschiebt den Schieberegler nach rechts */
        }

        .label-text {
            color: #555; /* Textfarbe */
            font-weight: 500; /* Schriftgewicht */
        }


        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 15px auto;
            }

            .button-group {
                flex-direction: column;
                width: 100%;
            }

            input[type="submit"] {
                width: 100%;
                margin-bottom: 8px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <?php if(!empty($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <h1><?php echo $taskName; ?></h1>

    <form method="POST" action="/page/submit">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        <?php foreach($fields as $fieldKey => $field): ?>
            <div class="form-group">
                <?php include __DIR__ . "/fields/{$field['type']}.php"; ?>
            </div>
        <?php endforeach; ?>

        <div class="button-group">
            <?php if($showBackButton): ?>
                <input type="submit" name="submitBack" value="Back"> <!-- Changed to English -->
            <?php endif; ?>
            <?php if($showNextButton): ?>
                <input type="submit" name="submitNext" value="Next"> <!-- Changed to English -->
            <?php endif; ?>
            <?php if($showCompleteButton): ?>
                <input type="submit" name="submitComplete" value="Complete">
            <?php endif; ?>
            <input type="submit" name="submitReset" value="Reset Installation" >
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const resetButton = document.querySelector('input[name="submitReset"]');
        if (resetButton) {
            resetButton.addEventListener('click', function(event) {
                if (!confirm('Are you sure you want to reset the installation? This action cannot be undone.')) {
                    event.preventDefault(); // Prevent form submission if user cancels
                }
            });
        }
    });
</script>

</body>
</html>