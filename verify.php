<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Student Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title text-center">Verify Your Email</h3>
                        <form action="verify_process.php" method="POST">
                            <div class="mb-3">
                                <label for="verification_code" class="form-label">Enter Verification Code</label>
                                <input type="text" class="form-control" id="verification_code" name="verification_code" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Verify Email</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 