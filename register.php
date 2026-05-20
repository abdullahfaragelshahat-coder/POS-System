<?php
session_start();
include 'connect.php'; // الربط على store_db

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($email) || empty($password)) {
        $message = "<div class='error'>جميع الحقول مطلوبة!</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='error'>البريد الإلكتروني غير صالح!</div>";
    } else {
        // فحص هل الإيميل مسجل قبل كده ولا لأ
        $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->execute([$email]);
        
        if ($checkEmail->rowCount() > 0) {
            $message = "<div class='error'>هذا البريد الإلكتروني مسجل بالفعل!</div>";
        } else {
            // تشفير الباسورد للحماية
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // إدخال المستخدم الجديد (تلقائياً بياخد رتبة cashier)
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'cashier')");
            
            if ($stmt->execute([$username, $email, $hashedPassword])) {
                $message = "<div class='success'>تم إنشاء الحساب بنجاح! جاري تحويلك للمنظومة...</div>";
                
                // جلب بيانات الحساب الجديد لعمل تسجيل دخول تلقائي له
                $user_id = $conn->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'cashier';

                // تحويله لشاشة البيع بعد 2 ثانية
                header("refresh:2;url=sales.php");
            } else {
                $message = "<div class='error'>حدث خطأ أثناء التسجيل، حاول مجدداً.</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إنشاء حساب جديد</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:Arial, sans-serif; }
        body { background:#e9ecef; display:flex; justify-content:center; align-items:center; height:100vh; }
        .container { width:400px; background:#fff; padding:35px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.1); border-top: 5px solid #b8952e; }
        h2 { text-align: center; margin-bottom: 25px; color: #333; font-size: 24px; }
        form { display: flex; flex-direction: column; gap: 15px; }
        input, button { padding:12px 15px; border-radius:6px; border:1px solid #ccc; font-size: 15px; outline: none; }
        input:focus { border-color: #b8952e; box-shadow: 0 0 5px rgba(184, 149, 46, 0.3); }
        button { background:#b8952e; color:#fff; font-weight:bold; cursor:pointer; border:none; transition: 0.2s; }
        button:hover { background: #967923; }
        .error { background:#f8d7da; color:#721c24; padding:10px; border-radius:6px; text-align:center; font-size:14px; font-weight: bold; }
        .success { background:#d4edda; color:#155724; padding:10px; border-radius:6px; text-align:center; font-size:14px; font-weight: bold; }
        .footer-link { text-align: center; margin-top: 20px; font-size: 14px; }
        .footer-link a { color: #b8952e; text-decoration: none; font-weight: bold; }
        .footer-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <h2>إنشاء حساب موظف جديد 📝</h2>
    <?php echo $message; ?>
    
    <form method="POST">
        <input type="text" name="username" placeholder="الاسم الكامل للموظف" required>
        <input type="email" name="email" placeholder="البريد الإلكتروني للعمل" required>
        <input type="password" name="password" placeholder="كلمة المرور السرية" required>
        <button type="submit">تسجيل الحساب وانطلاق 🚀</button>
    </form>

    <div class="footer-link">
        لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول من هنا</a>
    </div>
</div>

</body>
</html>