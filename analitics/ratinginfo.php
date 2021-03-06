 <script type="text/javascript" src="../testcenter/datepicker/jquery.js"></script>
 
<?php  
include "class/function.php";
include "auth.php";
if ($_SESSION['name_sesion_a']=="admin"){
include "menu.php";
include "navigate.php";
require_once "class/mysql_class_tdmu.php";
require_once "class/mysql_class_tdmu_rating.php";

//Get select controls data
$base_tdmu = new class_mysql_base_tdmu();
$ratingindex = $base_tdmu->select("SELECT DISTINCT index_id, index_text FROM tr_teacher_indices;");
//$base_tdmu = new class_mysql_base_tdmu();
$department = $base_tdmu->select("SELECT kaf_id, kaf_name FROM tbl_tech_kaf_folder ORDER BY kaf_name;");
//print_r($ratingindex);
//print_r($department);
echo "<center><h2>���� �������� ����������</h2>";
echo "<form action='ratinginfo.php?".$_SERVER['QUERY_STRING']."' method='POST' enctype='multipart/form-data'>";
//Draw indices selector
echo "<table width=100%><tr><td bgcolor=gray valign=top><center>";
navigate('������� ��������:',$ratingindex,'INDEXID');
echo "</td></tr>";
//Draw departmnet selector
echo "<tr><td bgcolor=gray valign=top><center>";
navigate('������� (�� ��):',$department,'DEPARTMENT');
echo "</td></tr>";
//Draw options checkboxes
echo "<tr><td bgcolor=gray valign=top><center>";
    if ($_POST['DETAIL']=="on"){
        echo"<input type='checkbox' class='DETAIL' name='DETAIL' CHECKED> "." - �������� ����������"."<br>";
    } else {
        echo"<input type='checkbox' class='DETAIL' name='DETAIL'> "." - �������� ����������"."<br>";
    }
    if ($_POST['SUMMARY']=="on"){
        echo"<input type='checkbox' class='SUMMARY' name='SUMMARY' CHECKED> "." - ������� ����������"."<br>";
    } else {
        echo"<input type='checkbox' class='SUMMARY' name='SUMMARY'> "." - ������� ����������"."<br>";
    }
echo "</td></tr></table>";
echo "<br><center><input type='submit' name='var' value='�������'><br></form>";

    if (!$_POST['INDEXID']==0){
        //Processing will start only if someone parameter is selected
	for ($k=0;$k<count($ratingindex);$k++)
	{
	  if ($_POST['INDEXID']==$ratingindex[$k][0]) { $param_name=$ratingindex[$k][1]; }
	}
        if ($_POST['DETAIL']=="on") {
            //Processing "show detail info" checkbox option
            if (!$_POST['DEPARTMENT']==0) {
// print_r($_POST['INDEXID']);
// print_r('<-index dep->');
// print_r($_POST['DEPARTMENT']);
                //Get detail data for a selected department
                $detail_mas =$base_tdmu->select("SELECT tk.kaf_name, tn.name, tiv.index_value
                                        FROM `tr_teacher_indices_values` tiv
                                        inner JOIN `tbl_tech_name` tn ON tiv.teacher_id=tn.name_id 
                                        inner JOIN `tbl_tech_kaf_folder` tk ON tk.kaf_id = (
                                            SELECT tjm.kaf_id FROM tbl_tech_journals tjm WHERE tjm.name_id = tn.name_id LIMIT 1
                                        )
                                        WHERE (tiv.index_id =".$_POST['INDEXID'].") AND (tiv.index_value >0) AND (tk.kaf_id=".$_POST['DEPARTMENT'].") order by tk.kaf_name, tn.name");
//print_r('<br>');
//print_r($detail_mas);
            } else {
                //Get detail data for all departments
                $detail_mas =$base_tdmu->select("SELECT tk.kaf_name, tn.name, tiv.index_value
                                                FROM `tr_teacher_indices_values` tiv
                                        inner JOIN `tbl_tech_name` tn ON tiv.teacher_id=tn.name_id 
                                        inner JOIN `tbl_tech_kaf_folder` tk ON tk.kaf_id = (
                                            SELECT tjm.kaf_id FROM tbl_tech_journals tjm WHERE tjm.name_id = tn.name_id LIMIT 1
                                        )
                                                WHERE (tiv.index_id =".$_POST['INDEXID'].") AND (tiv.index_value >0) order by tk.kaf_name, tn.name");            
            }
            //Display detail data table
            echo "<center><h3>�������� ���������� �� ������(��)</h3>";       
            echo" <table bgcolor='white' border=1 width = 100% class='ser'><tr><td colspan=3><center><b>".$param_name."</b></td></tr><tr><td><center><b>����� �������</td><td><center><b>�.�.�. ���������</b></td><td><center><b>�������� ���������</b></td></tr>";
            for ($i=0;$i<count($detail_mas);$i++)
            {
                echo "<tr>";
                for($j=0;$j<count($detail_mas[0]);$j++)
                {
                    if ($j<2) {
                        $cell_alignment = "<left>";
                    } else {
                        $cell_alignment = "<center>";
                    }
                    echo "<td>".$cell_alignment.$detail_mas[$i][$j]."</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } 
        if ($_POST['SUMMARY']=="on") {
            //Processing "show summary info" checkbox option
            if (!$_POST['DEPARTMENT']==0) {
                //Get summary data for a selected department         
                $summary_mas =$base_tdmu->select("SELECT tk.kaf_name, SUM(tiv.index_value)
                                        FROM `tr_teacher_indices_values` tiv
                                        inner JOIN `tbl_tech_name` tn ON tiv.teacher_id=tn.name_id 
                                        inner JOIN `tbl_tech_kaf_folder` tk ON tk.kaf_id = (
                                            SELECT tjm.kaf_id FROM tbl_tech_journals tjm WHERE tjm.name_id = tn.name_id LIMIT 1
                                        )
                                        WHERE (tiv.index_id =".$_POST['INDEXID'].") AND (tiv.index_value >0) AND (tk.kaf_id=".$_POST['DEPARTMENT'].") order by tk.kaf_name");
            } else {
                //Get summary data for all departments            
                $summary_mas =$base_tdmu->select("SELECT tk.kaf_name, SUM(tiv.index_value)
                                                FROM `tr_teacher_indices_values` tiv
                                                inner JOIN `tbl_tech_name` tn ON tiv.teacher_id=tn.name_id 
                                                inner JOIN `tbl_tech_kaf_folder` tk ON tk.kaf_id = (
                                                    SELECT tjm.kaf_id FROM tbl_tech_journals tjm WHERE tjm.name_id = tn.name_id LIMIT 1
                                                )
                                                WHERE (tiv.index_id =".$_POST['INDEXID'].") AND (tiv.index_value >0) GROUP BY tk.kaf_id ORDER BY tk.kaf_name, tn.name");         
            }
            //Display summary data table
            echo "<center><h3>������� ���������� �� ������(��)</h3>";            
            echo" <table bgcolor='white' border=1 width = 100% class='ser'><tr><td colspan=2><center><b>".$param_name."</b></td></tr><tr><td><center><b>����� �������</b></td><td><center><b>������� �� ������</b></td></tr>";
            $grand_total = 0;
            for ($i=0;$i<count($summary_mas);$i++)
            {
                echo "<tr>";
                for($j=0;$j<count($summary_mas[0]);$j++)
                {
                    if ($j<1) {
                        $cell_alignment = "<left>";
                    } else {
                        $cell_alignment = "<center>";
                    }
                    echo "<td>".$cell_alignment.$summary_mas[$i][$j]."</td>";
                    $grand_total = $grand_total + $summary_mas[$i][$j];//Calculate total for whole university
                }
                echo "</tr>";
            }
            echo "</table>";
            //Display total for whole university data table
            if ($_POST['DEPARTMENT']==0) {
                echo "<center><h3>������� ���������� �� ������������</h3>";
                echo" <table bgcolor='white' border=1 width = 100% class='ser'><tr><td><center><b>����� ���������</td><td><center><b>������� �������� ���������</b></td></tr>";
                echo" <tr><td><left><b>".$ratingindex[($_POST['INDEXID']-1)][1]."</b></td><td><center><b>".$grand_total."</b></td></tr></table>";              
            }
        }
    }
}else {header("Location: index.php");}
?> 
