<?php

namespace App\Model\CreateDocuments;

use App\AllowedDiscipline;
use App\CacheDepartment;
use App\CacheSpeciality;
use App\GradesFiles;
use App\Model\Contingent\Students;
use Chumper\Zipper\Facades\Zipper;
use Illuminate\Database\Eloquent\Model;
use App\Grades;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class Statistics extends Model
{

    protected $DOC_PATH; // for puts files
	
	public $docfilename;

    protected $studentsOfGroup; // Students of group

    protected $studentOfModule;

    protected $dataGradesOfStudentsGroups; // for find all groups

    protected $dataOfFile; // each module

    protected $dataEachOfFile; // select module

    protected $speciality; // get from cache speciality

    protected $department; // get from cache department

    protected $idFileGrade = 0;

    protected $shablons = [];
	
	protected $modulesByDiscipline = []; //number of modules per discipline

    private $sumGrades = [];

    private $countOfAll2 = [];

    private $conver = [];

    private $EDUBASISID = [];
	
	private $AllStudentsEduBasisid = [];

    public function __construct($idFileGrade)
    {
        $this->conver = Config::get('grade-proportional');
        $this->dataOfFile = GradesFiles::where('file_info_id', $idFileGrade)->get();
//        $this->dataOfFile = GradesFiles::select('Semester','DepartmentId','SpecialityId','DisciplineVariantID','ModuleVariantID','ModuleNum','NameDiscipline','NameModule','xml_file_id','file_info_id')
//            ->where('ModuleVariantID',$this->dataOfFile->ModuleVariantID)
//            ->where('DepartmentId', $this->dataOfFile->DepartmentId)
//            ->distinct('DepartmentId')
//            ->get();
        $this->EDUBASISID = Students::getSumContractOrButjetStudent(Grades::select('id_student')->where('grade_file_id', $this->dataOfFile->first()->id)->get()->toArray());
        /**
         * get data from bd about module (generals data for each docs)
         */
        $this->speciality = CacheSpeciality::getSpeciality($this->dataOfFile->first()->SpecialityId)->name;
        $this->department = CacheDepartment::getDepartment($this->dataOfFile->first()->DepartmentId)->name;
		
		//create list of modules by discipline - disable - not used
		//$this->modulesByDiscipline = $this->getModulesListByDiscipline();
		//construct the filename for downloading
		switch($this->dataOfFile[0]->type_exam_id){
			case 1:
				$tmpexamtype = 'модуль';
				break;
			case 2:
				$tmpexamtype = 'іспит';
				break;
			case 3:
				$tmpexamtype = 'дифзалік';
				break;
			default:
				$tmpexamtype = '';
		}
		$tmpdepartment = ($this->department == 'факультет по роботі з іноземними студентами') ? 'Факультет іноземних студентів' : $this->mb_ucfirst($this->department);
		$tmpdocfilename = $tmpdepartment .'_'. $this->dataOfFile[0]->Semester . '-cеместр_групи-' .$this->_getAllGroup() .'_'. $tmpexamtype .'_'. date('d-m-Y', strtotime($this->dataOfFile[0]->created_at));
		$tmpdocfilename = transliterator_transliterate ('Any-Latin; [\u0100-\u7fff] Remove; Latin-ASCII; NFD; [:Nonspacing Mark:] Remove; NFC; Lower();', $tmpdocfilename);
		$this->docfilename = preg_replace('/[^A-Za-z0-9_-]/', '_', $tmpdocfilename );
    }

    /**
     * @return array
     */
    public function formGeneralStat($isDownload=false)
    {
        $table = '';
        $i = 1;
        foreach ($this->dataOfFile as $this->dataEachOfFile) {
            $this->studentOfModule = Grades::where('grade_file_id', $this->dataEachOfFile->id)->get();
            $this->sumGrades = $this->getSumGradesFromEachStudent();
            $table .= '<tr><td>' . $i . '</td><td>' . $this->findSemester() . '</td>';
			//if (count($this->modulesByDiscipline[$this->dataEachOfFile->DisciplineVariantID]) > 1 ){ //by number - improper
			if ($this->dataEachOfFile->type_exam_id == 1 ){	//best - by exam type ID
				$table .= '<td>' . $this->dataEachOfFile->NameDiscipline . ' - ' . $this->dataEachOfFile->ModuleNum . '. ' . $this->dataEachOfFile->NameModule . '</td>';
			} else {
				$table .= '<td>' . $this->dataEachOfFile->NameDiscipline . '</td>';
			}
            $table .= '<td>' . count($this->studentOfModule) . '</td>
                    <td>'.$this->sumGrades['gradeOfFiveTypes']['stat']['2'].' ('.number_format($this->sumGrades['gradeOfFiveTypes']['stat']['2'] / count($this->studentOfModule)*100, 2).'%)</td>
                    <td>'.($this->sumGrades['gradeOfFiveTypes']['stat']['B']['3']+$this->sumGrades['gradeOfFiveTypes']['stat']['C']['3']).' ('.number_format(($this->sumGrades['gradeOfFiveTypes']['stat']['C']['3']+$this->sumGrades['gradeOfFiveTypes']['stat']['B']['3']) / count($this->studentOfModule)*100, 2).'%)</td>
                    <td>'.($this->sumGrades['gradeOfFiveTypes']['stat']['B']['4']+$this->sumGrades['gradeOfFiveTypes']['stat']['C']['4']).' ('.number_format(($this->sumGrades['gradeOfFiveTypes']['stat']['C']['4']+$this->sumGrades['gradeOfFiveTypes']['stat']['B']['4']) / count($this->studentOfModule)*100, 2).'%)</td>
                    <td>'.($this->sumGrades['gradeOfFiveTypes']['stat']['B']['5']+$this->sumGrades['gradeOfFiveTypes']['stat']['C']['5']).' ('.number_format(($this->sumGrades['gradeOfFiveTypes']['stat']['C']['5']+$this->sumGrades['gradeOfFiveTypes']['stat']['B']['5']) / count($this->studentOfModule)*100, 2).'%)</td>
                    ';
            $table .= '<td>' . number_format($this->sumGrades['examGrade'] / count($this->studentOfModule), 2) . '</td>';
            $table .= '<td>' . number_format($this->sumGrades['grade'] / count($this->studentOfModule), 2) . '</td>';
            $table .= '</td><td></td><td></td><td></tr>';
            $i++;
        }

        $this->shablons['body'] = '';
        $this->shablons['title'] = trans("admin/modules/stat.gStat");
		if ($isDownload) {
			$this->shablons['body'] .= $this->HTML2DOCHeader();
			$this->shablons['body'] .= $this->formHeader();
			//$this->shablons['body'] .= '<p style="font-size:12pt;">Не склало – '.count($this->countOfAll2).' ('.number_format(count($this->countOfAll2) / count($this->studentOfModule)*100, 2).'%)</p>';
			$this->shablons['body'] .= '<p style="font-size:12pt;">Не склало – '.count($this->countOfAll2).' ('.number_format(count($this->countOfAll2) / count($this->AllStudentsEduBasisid)*100, 2).'%)</p>';
		} else {
			$this->shablons['body'] .= $this->formHeader();
			//$this->shablons['body'] .= '<p>Не склало – '.count($this->countOfAll2).' ('.number_format(count($this->countOfAll2) / count($this->studentOfModule)*100, 2).'%)</p>';
			$this->shablons['body'] .= '<p>Не склало – '.count($this->countOfAll2).' ('.number_format(count($this->countOfAll2) / count($this->AllStudentsEduBasisid)*100, 2).'%)</p>';
		}
		$this->shablons['body'] .= '<table class="table table-hover" style="width:100%; font-size:9pt;" border="1">';
        $this->shablons['body'] .= '<tr><td>№</td><td>Курс</td><td> Назва дисципліни</td><td>Загальна кількість студентів</td><td>Кількість студентів , що склали дисципліну на \'незадовіль-но\' (відсоток)';
        $this->shablons['body'] .= '</td><td>Кількість студентів , що склали дисципліну на \'задовільно\' (відсоток)</td><td>Кількість студентів , що склали дисципліну на \'добре\' (відсоток)</td><td>Кількість студентів , що склали дисципліну на \'відмінно\' (відсоток)';
        $this->shablons['body'] .= '</td><td>Cередній бал </td> <td>Середній бал поточної успішності</td><td>Важкі</td><td>Легкі</td><td>Середній показник</td></tr>';

        $this->shablons['body'] .= $table.'</table>';
//        $this->shablons['body'] .= $this->formFooter();
		if ($isDownload) {$this->shablons['body'] .= $this->HTML2DOCFooter();}
        return $this->shablons;
    }

    /**
     * @return array
     */
    public function formGeneralBKStat($isDownload=false)
    {
        $table = '';
        $i = 1;
        foreach ($this->dataOfFile as $this->dataEachOfFile) {
            $this->studentOfModule = Grades::where('grade_file_id', $this->dataEachOfFile->id)->get();
            $this->sumGrades = $this->getSumGradesFromEachStudent();
            $table .= '<tr><td>' . $i . '</td><td>' . $this->findSemester() . '</td>';
			//if (count($this->modulesByDiscipline[$this->dataEachOfFile->DisciplineVariantID]) > 1 ){
			if ($this->dataEachOfFile->type_exam_id == 1 ){	//best - by exam type ID
				$table .= '<td>' . $this->dataEachOfFile->NameDiscipline . ' - ' . $this->dataEachOfFile->ModuleNum . '. ' . $this->dataEachOfFile->NameModule . '</td>';
			} else {
				$table .= '<td>' . $this->dataEachOfFile->NameDiscipline . '</td>';
			}
            $table .= '<td>' . count($this->studentOfModule) . '</td>
                    <td>'.$this->sumGrades['gradeOfFiveTypes']['stat']['C']['2'].' ('.number_format($this->sumGrades['gradeOfFiveTypes']['stat']['C']['2'] / count($this->studentOfModule)*100, 2).'%)</td>
                    <td>'.$this->sumGrades['gradeOfFiveTypes']['stat']['B']['2'].' ('.number_format($this->sumGrades['gradeOfFiveTypes']['stat']['B']['2'] / count($this->studentOfModule)*100, 2).'%)</td>
                    <td>'.$this->sumGrades['gradeOfFiveTypes']['stat']['C']['3'].' ('.number_format($this->sumGrades['gradeOfFiveTypes']['stat']['C']['3'] / count($this->studentOfModule)*100, 2).'%)</td>
                    <td>'.$this->sumGrades['gradeOfFiveTypes']['stat']['B']['3'].' ('.number_format($this->sumGrades['gradeOfFiveTypes']['stat']['B']['3'] / count($this->studentOfModule)*100, 2).'%)</td>
                    <td>'.$this->sumGrades['gradeOfFiveTypes']['stat']['C']['4'].' ('.number_format($this->sumGrades['gradeOfFiveTypes']['stat']['C']['4'] / count($this->studentOfModule)*100, 2).'%)</td>
                    <td>'.$this->sumGrades['gradeOfFiveTypes']['stat']['B']['4'].' ('.number_format($this->sumGrades['gradeOfFiveTypes']['stat']['B']['4'] / count($this->studentOfModule)*100, 2).'%)</td>
                    <td>'.$this->sumGrades['gradeOfFiveTypes']['stat']['C']['5'].' ('.number_format($this->sumGrades['gradeOfFiveTypes']['stat']['C']['5'] / count($this->studentOfModule)*100, 2).'%)</td>
                    <td>'.$this->sumGrades['gradeOfFiveTypes']['stat']['B']['5'].' ('.number_format($this->sumGrades['gradeOfFiveTypes']['stat']['B']['5'] / count($this->studentOfModule)*100, 2).'%)</td>
                    ';
            $table .= '<td>' . number_format($this->sumGrades['examGrade'] / count($this->studentOfModule), 2) . '</td>';
            $table .= '<td>' . number_format($this->sumGrades['examGrade'] / count($this->studentOfModule), 2) . '</td>';
            $table .= '<td>' . number_format($this->sumGrades['grade'] / count($this->studentOfModule), 2) . '</td>';
            $table .= '<td>' . number_format($this->sumGrades['grade'] / count($this->studentOfModule), 2) . '</td>';
            $table .= '</td></tr>';
            $i++;
        }
		//new method to calc total students
		$this->EDUBASISIDLocal = $this->getSumContractOrButjetStudentLocal();
		
        $this->shablons['body'] = '';
        $this->shablons['title'] = trans("admin/modules/stat.gBCStat");
		if ($isDownload) {
			$this->shablons['body'] .= $this->HTML2DOCHeader();
			$this->shablons['body'] .= $this->formHeader();
			//$this->shablons['body'] .= '<p style="font-size:12pt;">Кількість студентів - '. strval(intval($this->EDUBASISID["B"])+intval($this->EDUBASISID["C"])) .' ( Бюджет - ' . $this->EDUBASISID["B"] .', Контракт - ' . $this->EDUBASISID["C"] .')</p>';
			$this->shablons['body'] .= '<p style="font-size:12pt;">Кількість студентів - '. count($this->AllStudentsEduBasisid) .' ( Бюджет - ' . $this->EDUBASISIDLocal["B"] . ', Контракт - ' . $this->EDUBASISIDLocal["C"] .')</p>';
			//$this->shablons['body'] .= '<p style="font-size:12pt;">Не склало – '.count($this->countOfAll2).' ('.number_format(count($this->countOfAll2) / count($this->studentOfModule)*100, 2).'%)</p>';
			$this->shablons['body'] .= '<p style="font-size:12pt;">Не склало – '.count($this->countOfAll2).' ('.number_format(count($this->countOfAll2) / count($this->AllStudentsEduBasisid)*100, 2).'%)</p>';
		} else {
			//$this->shablons['body'] .= $this->formHeader('Кількість студентів - '. strval(intval($this->EDUBASISID["B"])+intval($this->EDUBASISID["C"])).' ( Бюджет - ' . $this->EDUBASISID["B"] . ', Контракт - ' . $this->EDUBASISID["C"] .')');
			$this->shablons['body'] .= $this->formHeader('Кількість студентів - '. count($this->AllStudentsEduBasisid) .' ( Бюджет - ' . $this->EDUBASISIDLocal["B"] . ', Контракт - ' . $this->EDUBASISIDLocal["C"] .')');
			//$this->shablons['body'] .= '<p>Не склало – '.count($this->countOfAll2).' ('.number_format(count($this->countOfAll2) / count($this->studentOfModule)*100, 2).'%)</p>';
			$this->shablons['body'] .= '<p>Не склало – '.count($this->countOfAll2).' ('.number_format(count($this->countOfAll2) / count($this->AllStudentsEduBasisid)*100, 2).'%)</p>';
		}
		$this->shablons['body'] .= '<table class="table table-hover" style="width:100%; font-size:9pt;" border="1" >';
        $this->shablons['body'] .= '<tr><td>№</td><td>Курс</td><td> Назва дисципліни</td><td>Загальна кількість студентів</td><td>Кількість контрактних студентів , що склали дисципліну на \'незадовіль-но\' (відсоток)</td><td>Кількість державних студентів , що склали дисципліну на \'незадовіль-но\' (відсоток)';
        $this->shablons['body'] .= '</td><td>Кількість контрактних студентів , що склали дисципліну на \'задовільно\' (відсоток)</td><td>Кількість державних студентів , що склали дисципліну на \'задовільно\' (відсоток)</td><td>Кількість контрактних студентів , що склали дисципліну на \'добре\' (відсоток)</td><td>Кількість державних студентів , що склали дисципліну на \'добре\' (відсоток)</td><td>Кількість контрактних студентів , що склали дисципліну на \'відмінно\' (відсоток)</td><td>Кількість державних студентів , що склали дисципліну на \'відмінно\' (відсоток)';
        $this->shablons['body'] .= '</td><td>Cередній бал контрактних студентів</td><td>Cередній бал державних студентів </td> <td>Середній бал поточної успішності контрактних студентів</td><td>Середній бал поточної успішності державних студентів</td></tr>';
        $this->shablons['body'] .= $table.'</table>';
//        $this->shablons['body'] .= $this->formFooter();
		if ($isDownload) {$this->shablons['body'] .= $this->HTML2DOCFooter();}
        return $this->shablons;
    }

    public function formDetailedStat($isDownload=false)
    {
        $this->shablons['body'] = '';
        $this->shablons['title'] = trans("admin/modules/stat.detailStat");
        $this->shablons['body'] .= $this->formHeader();
        $this->shablons['body'] .= '<tr><td>Група</td><td>П.І.Б</td>';
        foreach ($this->dataOfFile as $this->dataEachOfFile) {
            $this->studentOfModule = Grades::where('grade_file_id', $this->dataEachOfFile->id)->get()->sortBy('group');
                foreach ($this->studentOfModule as $student) {
                    $studentForForm[$student->group][$student->id_student][$this->dataEachOfFile->id]['grade'] = $student->grade;
                    $studentForForm[$student->group][$student->id_student][$this->dataEachOfFile->id]['examGrade'] = $student->exam_grade;
                    $studentForForm[$student->group][$student->id_student]['fio'] = $student->fio;
                }
            //if (count($this->modulesByDiscipline[$this->dataEachOfFile->DisciplineVariantID]) > 1 ){
			if ($this->dataEachOfFile->type_exam_id == 1 ){	//best - by exam type ID
				$this->shablons['body'] .= '<td>'.$this->dataEachOfFile->NameDiscipline.' - ('.$this->dataEachOfFile->ModuleNum.'.'.$this->dataEachOfFile->NameModule.')';
			} else {
				$this->shablons['body'] .= '<td>'.$this->dataEachOfFile->NameDiscipline;
			}
            $this->shablons['body'] .= '<table width="100%"><tr><td width="50%"><b>Grade</b></td><td><b>Exam Grade</b></td></tr></table></td>';
        }

        $this->shablons['body'] .= '</tr>';
            foreach ($studentForForm as $keyGroup=>$group) {
                foreach ($group as $students) {
                    $this->shablons['body'] .= '<tr><td>'.$keyGroup.'</td><td>'.$students['fio'].'</td>';
                    foreach($students as $studentgrade){
                        if (is_array($studentgrade)){
                            $this->shablons['body'] .= '<td><table width="100%"><tr><td width="50%">'.$studentgrade['grade'].'</td><td>'.$studentgrade['examGrade'].'</td></tr></table> </td>';
                        }

                    }
                    $this->shablons['body'] .= '</tr>';
                }
            }
		//$this->shablons['body'] .= '</table>';	
        $this->shablons['body'] .= $this->formFooter();
        return $this->shablons;
    }

    public function formHeader($beforeTable = '')
    {
        $this->department = ($this->department == 'факультет по роботі з іноземними студентами') ? 'Факультет іноземних студентів' : $this->mb_ucfirst($this->department);
        $text = '';
        $text .= '<p align=center style="font-size:12pt;"><strong>'. $this->department .', '.$this->findSemester().' - курс, групи: '.$this->_getAllGroup().', '.(($this->sumGrades['gradeOfFiveTypes']['type']=='exam')?' Іспит "' . $this->dataOfFile[0]->NameDiscipline . '"' : ' Диференційований залік' ).', '. date('d.m.Y', strtotime($this->dataOfFile[0]->created_at)) .'</strong></p>';
		if ($beforeTable <> '') { $text .= $beforeTable.'<br>';	}

        return $text;
    }

    public function formFooter()
    {
        $text = '';
		//$text .= '</table>Загальні дані: <br> Не склало – '.count($this->countOfAll2).' ('.number_format(count($this->countOfAll2) / count($this->studentOfModule)*100, 2).'%)';
		$text .= '</table>Загальні дані: <br> Не склало – '.count($this->countOfAll2).' ('.number_format(count($this->countOfAll2) / count($this->AllStudentsEduBasisid)*100, 2).'%)';

        return $text;
    }
	
	public function HTML2DOCHeader()
    {
		$text = '';
        $text .= '<!DOCTYPE html><html><head><meta charset=\"UTF-8\">';
		$text .= '<style>
html, body {
    margin: 0;
    padding: 0;
    font-family: "Times New Roman", Georgia, Serif;
    font-size: 12px;
    background: #eee;
}
th {
    border: solid 1px #999;
	padding: 2px;
    background: #eee;
}
td {
    border: solid 1px #999;
	padding: 2px;
    background: #fff;
}
tr:hover td {
    background: #eef;
}
table {
    border-collapse: collapse;
    border-spacing: 0pt;
    width: 100%;
    margin: auto;
}
</style>
</head>
<body>';

        return $text;
	}

	public function HTML2DOCFooter()
    {
		$text = '';
        $text .= '</body></html>';

        return $text;
	}
	
    /**
     * Convert semester to course
     * @return int
     */
    private function findSemester()
    {
        return ($this->dataEachOfFile->Semester & 1) ? ($this->dataEachOfFile->Semester + 1) / 2 : $this->dataEachOfFile->Semester / 2;
    }

    private function getSumGradesFromEachStudent()
    {

        $sum['examGrade'] = Grades::select('exam_grade')->where('grade_file_id', $this->dataEachOfFile->id)->sum('exam_grade');
        $sum['grade'] = Grades::select('grade')->where('grade_file_id', $this->dataEachOfFile->id)->sum('grade');
        $sum['gradeOfFiveTypes'] = $this->convertGrades();
        return $sum;
    }
	
	//create array of pairs "modules id's-names" by discipline
	private function getModulesListByDiscipline()
    {
		$tmpModulesList = [];
		foreach ($this->dataOfFile as $this->dataEachOfFile) {
			if (array_key_exists($this->dataEachOfFile->DisciplineVariantID,$tmpModulesList)){
				if (!array_key_exists($this->dataEachOfFile->ModuleNum,$tmpModulesList[$this->dataEachOfFile->DisciplineVariantID])){
					$tmpModulesList[$this->dataEachOfFile->DisciplineVariantID][$this->dataEachOfFile->ModuleNum] = $this->dataEachOfFile->NameModule;
				}
			} else {
				$tmpModulesList[$this->dataEachOfFile->DisciplineVariantID][$this->dataEachOfFile->ModuleNum] = $this->dataEachOfFile->NameModule;
			}
		}
		return $tmpModulesList;
	}

    private function convertGrades(){
        $qty = ($this->dataEachOfFile->qty_questions)?$this->dataEachOfFile->qty_questions:24; /*small bag fix because , because , because )))) ahahaha*/
        $type = ($this->dataEachOfFile->type_exam_id==2)?'exam':($this->dataEachOfFile->type_exam_id==1)?(AllowedDiscipline::where('arrayAllowed', 'like', '%'.$this->dataEachOfFile->DisciplineVariantID.'%')->get()->first())?'exam':'dz':'dz';
        $fromConfigArray = $this->conver[$type][$qty];

        $data = ['stat'=>[
            'B'=>['2'=>0,'3'=>0, '4'=>0, '5'=>0],
            'C'=>['2'=>0,'3'=>0, '4'=>0, '5'=>0],
            '2'=>0],
            'type'=>$type];

        foreach ($this->studentOfModule as $student) {
            $eduBasisid = Students::getStudentEDUBASISID($student->id_student);
			$this->AllStudentsEduBasisid[$student->id_student] = $eduBasisid;
            if($student->exam_grade==0) {
                $data['stat'][$eduBasisid]['2']++;
                $data['stat']['2']++;
                $this->countOfAll2[$student->id_student]=true;
            }
            foreach($fromConfigArray as $keyGrade=>$convert){
                if($convert['from']<=$student->exam_grade && $convert['to']>=$student->exam_grade){
                    $data['stat'][$eduBasisid][$keyGrade]++;
                }if($student->exam_grade==0){
                }
            }

        }
        
        return $data;
    }

    private function getSumContractOrButjetStudentLocal(){
        $basisid = ['C'=>0,'B'=>0];
        foreach($this->AllStudentsEduBasisid as $studentId=>$eduBasisId){
			$eduBasisId ==='C'?$basisid['C']++:$basisid['B']++;
        }
        return $basisid;
    }
	
    private function mb_ucfirst($value)
    {
        $firstLetter = mb_strtoupper(mb_substr($value, 0, 1), 'UTF-8');
        $otherLetters = mb_substr($value, 1);

        return $firstLetter . $otherLetters;
    }

    private function _getAllGroup(){
        $groups = [];
        $transform_groups = [];
        foreach($this->dataOfFile as $module){
            $groups = Grades::select('group')->where('grade_file_id',$module->id)->distinct()->get()->lists('group','group')->toArray()+$groups;
        }
        $groups = array_sort_recursive($groups);

        $count = 1;

        foreach($groups as $key => $group){
            if (isset($groups[$key]))
                $transform_groups[$count][] = $groups[$key];
                if (isset($groups[$key+1])) {
                    $transform_groups[$count][] = $groups[$key+1];
                }
            else{
                $count++;
            }
        }
        foreach($transform_groups as $key => $group) {
            if (count($group) > 1) {
                $finalGroups[] = $group[0] . ' - ' . $group[count($group) - 1];
            } else {
                $finalGroups[] = $group[0];
            }
        }

        return implode(', ',$finalGroups);
    }

}
