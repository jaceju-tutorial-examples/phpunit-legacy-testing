<?php

session_start();
define('DB_DSN', 'sqlite:./db.sqlite');
define('DB_USER', null);
define('DB_PASS', null);

try {
    $dbh = new PDO(DB_DSN, DB_USER, DB_PASS);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit;
}

if (!empty($_POST)) {
    $nickname   = isset($_POST['nickname']) ? strval($_POST['nickname']) : '';
    $message    = isset($_POST['message'])  ? strval($_POST['message'])  : '';
    $created_at = date('Y-m-d H:i:s');

    if ($nickname !== '' && $message !== '') {
        $stmt = $dbh->prepare('INSERT INTO post (nickname, message, created_at) VALUES(?, ?, ?)');
        $stmt->execute(array($nickname, $message, $created_at));
    } else {
        $_SESSION['old_input'] = array(
            'nickname' => $nickname,
            'message'  => $message,
        );
    }
    header('Location: /');
    exit;
} else {
    $stmt = $dbh->prepare('SELECT * FROM post ORDER BY id DESC');
    $stmt->execute();

    $old = array(
        'nickname' => '',
        'message'  => '',
    );
    $has_old = false;

    if (isset($_SESSION['old_input'])) {
        $old = $_SESSION['old_input'];
        unset($_SESSION['old_input']);
        $has_old = true;
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GBook</title>
</head>
<body>
        <h1>GBook</h1>

        <form action="/" method="POST" role="form">
            <input type="text" name="nickname" size="20" placeholder="暱稱" value="<?= $old['nickname'] ?>">
            <?php if ($has_old && $old['nickname'] === ''): ?>
                <p>Field 'nickname' is required.</p>
            <?php endif; ?>
            <br>
            <textarea name="message" cols="100" rows="5"><?= $old['message'] ?></textarea>
            <?php if ($has_old && $old['message'] === ''): ?>
                <p>Field 'message' is required.</p>
            <?php endif; ?>
            <br>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
        <?php while ($post = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
        <hr>
        <div class="post">
            <div class="message">
                <?= htmlspecialchars($post['message']); ?>
            </div>
            <br>
            <div class="nickname">
                <?= htmlspecialchars($post['nickname']); ?> - <?= htmlspecialchars($post['created_at']); ?>
            </div>
        </div>
        <?php endwhile; ?>
</body>
</html>
