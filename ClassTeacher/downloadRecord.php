<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

?>
        <table border="1">
        <thead>
            <tr>
            <th>#</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Father Name</th>
            <th>Admission No</th>
            <th>Class</th>
            <th>Semester</th>
            <th>Session</th>
            <th>Term</th>
            <th>Status</th>
            <th>Date</th>
            </tr>
        </thead>

<?php 
$filename="Attendance list";
$dateTaken = date("Y-m-d");

$cnt=1;			
$ret = mysqli_query($conn,"SELECT
    a.Id,
    COALESCE(a.status, '0') AS status,
    a.dateTimeTaken,
    c.className,
    arm.semisterName,
    sd.sessionName,
    d.divisionName,
    s.firstName,
    s.lastName,
    s.otherName,
    s.admissionNumber
  FROM tblstudents s
  INNER JOIN tblclass c ON c.Id = s.classId
  INNER JOIN tblclasssemister arm ON arm.Id = s.classArmId
  LEFT JOIN tblattendance a
    ON a.admissionNo = s.admissionNumber
    AND a.classId = '$_SESSION[classId]'
    AND a.classArmId = '$_SESSION[classArmId]'
    AND a.dateTimeTaken = '$dateTaken'
  LEFT JOIN tblsessiondivision sd ON sd.Id = a.sessiondivisionId
  LEFT JOIN tblDivision d ON d.Id = sd.divisionId
  WHERE s.classId = '$_SESSION[classId]' AND s.classArmId = '$_SESSION[classArmId]'
  ORDER BY s.firstName ASC");

if(mysqli_num_rows($ret) > 0 )
{
while ($row=mysqli_fetch_array($ret)) 
{ 
    
    if($row['status'] == '1'){$status = "Present"; $colour="#00FF00";}else{$status = "Absent";$colour="#FF0000";}

echo '  
<tr>  
<td>'.$cnt.'</td> 
<td>'.$firstName= $row['firstName'].'</td> 
<td>'.$lastName= $row['lastName'].'</td> 
<td>'.$otherName= $row['otherName'].'</td> 
<td>'.$admissionNumber= $row['admissionNumber'].'</td> 
<td>'.$className= $row['className'].'</td> 
<td>'.$semisterName=$row['semisterName'].'</td>	
<td>'.$sessionName=$row['sessionName'].'</td>	 
<td>'.$divisionName=$row['divisionName'].'</td>	
<td>'.$status=$status.'</td>	 	
<td>'.$dateTimeTaken=date('d-m-Y', strtotime($dateTaken)).'</td>	 					
</tr>  
';
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=".$filename."-report.xls");
header("Pragma: no-cache");
header("Expires: 0");
			$cnt++;
			}
	}
?>
</table>