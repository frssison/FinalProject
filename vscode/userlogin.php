<?php

require "dbcon.php";

session_start();
//USERNAME MIGHT BE CHANGED TO EMAIL

if (isset($_POST['username']) && isset($_POST['password'])) {
    function verify($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}

$uemail = verify($_POST['username']);
$pass = verify($_POST['password']);
$logintype = $_POST['logintype']; //=1 is customer, =2 is admin

//FOR USERS
$table = "user_credentials";
$uidres = RetrieveUser($table,$con,$uemail, "email");

if(mysqli_num_rows($uidres)===1){
    $uinfo_row = mysqli_fetch_assoc($uidres);
    if($uinfo_row['email'] === $uemail && password_verify($pass, $uinfo_row['password'])) {
        $uid = $uinfo_row['userinfo_id'];
        $ucred_id = $uinfo_row['usercred_id'];

        echo $uid;
        $aidres = RetrieveAdmin($con,$uid);
        if(mysqli_num_rows($aidres)===1 && $logintype === "2"){           //verified user and is admin
            $admin_row = mysqli_fetch_assoc($aidres);
            $aid = $admin_row['admin_id'];
            $ad_priv = $admin_row['user_privilege'];
            $ad_stat = $admin_row['admin_status'];

            if($ad_stat == "Active"){

            $usertype_id = 2; //admin id
            echo 'This is admin';
            $_SESSION['uid'] = $uid;
            $_SESSION['admin_id'] = $aid;
            
            if($ad_priv == 'Authorized'){
                $_SESSION['isPriv'] = 1;  
                echo $_SESSION['isPriv'] . $_SESSION['admin_id'] . $ad_priv;
                
                header("Location: ../adminside/homeadmin.php");     //AUTHORIZED ADMIN
                
            } else if($ad_priv == 'Unauthorized') {
                unset($_SESSION['isPriv']);                
                header("Location: ../adminside/homeadmin.php");    //UNAUTHORIZED ADMIN

            }

            $SuccessInsert = InsertUserLog( $con,$ucred_id,$usertype_id);// USER TYPE ID WILL BE CHANGED IN DIFF PAGE FOR VERIFICATION
            exit();
        } else {
            header("Location: ../adminside/adminLogin.php?error=You are not authorized to access the page.");
            exit();
        }
        } else if($logintype === "1")        
        {                                            //verified but customer only
            echo 'This is not admin';
            $usertype_id = 1; //customer id
            $_SESSION['uid'] = $uid;
            echo "SESSION UID: " . $_SESSION['uid'];
            $SuccessInsert = InsertUserLog( $con,$ucred_id,$usertype_id);// USER TYPE ID WILL BE CHANGED IN DIFF PAGE FOR VERIFICATION

            header("Location: ../customerside/homecustomer.php");
            exit();
        } else {
            echo 'user is not an admin and logged in in admin';
            header("Location: ../adminside/adminLogin.php?error=You are not authorized to access the page.");
            exit();
        }
    }else {
        echo 'Invalid password.';

        if($logintype === "1"){
            header("Location: ../adminside/index.php?error=Incorrect Password.");
            exit();
        } else if ($logintype === "2"){
            header("Location: ../adminside/adminLogin.php?error=Incorrect Password.");
            exit();
        } else {
            header("Location: ../adminside/index.php");
            exit();
        }

       
    }
} else {
    // $userIsRegistered = False;
    echo 'This user is not registered';

    if($logintype === "1"){
        header("Location: ../adminside/index.php?error=Invalid Credentials.");
        exit();
    } else if ($logintype === "2"){
        header("Location: ../adminside/adminLogin.php?error=Invalid Credentials.");
        exit();
    } else {
        header("Location: ../adminside/index.php");
        exit();
    }

}

function RetrieveUser($table, $con, $email, $conditionfield) {
    $retrievefield = "SELECT * FROM $table WHERE $conditionfield = ? ";
    $stmt = $con->prepare($retrievefield);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    return $stmt->get_result(); // Always return the result object
}

function RetrieveAdmin($con, $uid) {
    $retrievefield = "SELECT * FROM admin WHERE userinfo_id = ? AND admin_status = 'Active'";
    $stmt = $con->prepare($retrievefield);
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    return $stmt->get_result(); // Always return the result object
}

function InsertUserLog($con,$ucred_id,$usertype_id){
    $userlogin_query = "INSERT INTO user_login(userlogin_id,usercred_id,logindate,usertype_id) VALUES (".(TableRowCount("user_login",$con)+1). ",".$ucred_id.",NOW(),".$usertype_id.")";

    if (mysqli_query($con, $userlogin_query)) {
        echo "New record created successfully";
        return true;
    } else {
        echo "Error: " . $userlogin_query . "<br>" . mysqli_error($con);
        return false;
    }
}

function TableRowCount(string $table, $con)
{
    $query = "SELECT COUNT(*) AS total FROM " . $table;
    $count = 0;

    if ($results = mysqli_query($con, $query)) {
        $row = mysqli_fetch_assoc($results);
        $count = $row['total'];
    }

    return $count;
}