<!DOCTYPE html>
<html>

<head>
    <style type="text/css">
    body {
        font-family: arial;
        font-size: 16px;
        margin: 2em;
    }
    label {
        display: block;
    }
    input.text {
        font-size: 24px;
    }
    input.submit {
        font-size: 16px;
    }
    .error {
        color: #f33;
        margin-bottom: 1em;
    }
    </style>
</head>

<body>
    <form method="post">
        <?php if (!empty($error)) { ?>
            <div class="error"><?php echo $error ?></div>
        <?php } ?>
        <div class="field name">
            <label for="name">What is your name?</label>
            <input class="text" type="text" name="name"/>
            <input class="submit" type="submit" value="Do It"/>
        </div>
        <div class="submit">
            
        </div>
    </form>
</body>

</html>
