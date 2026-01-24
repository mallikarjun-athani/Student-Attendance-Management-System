<?php

include '../Includes/dbcon.php';

    $cid = intval($_GET['cid']);//

        // use new semester table/column names
        $queryss=mysqli_query($conn,"select * from tblclasssemister where classId=".$cid." ORDER BY semisterName ASC");                        
        $countt = mysqli_num_rows($queryss);

        echo '
        <select required name="classArmId" class="form-control mb-3" oninvalid="this.setCustomValidity(\'Semester is required.\')" oninput="this.setCustomValidity(\'\')">';
        echo'<option value="">--Select Semester--</option>';
        while ($row = mysqli_fetch_array($queryss)) {
        echo'<option value="'.$row['Id'].'" >'.$row['semisterName'].'</option>';
        }
        echo '</select>';
?>

