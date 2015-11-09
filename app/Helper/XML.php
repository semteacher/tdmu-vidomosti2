<?php

namespace App\Helper;

use App\ConsultingGrades;
use App\Grades;
use App\GradesFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Storage;
use Orchestra\Parser\Xml\Document;
use Orchestra\Parser\Xml\Reader;
use File as FileOr;

class XML extends Model
{
    protected $app;
    protected $document;
    protected $stub;
    protected $xml;

    public function __construct()
    {
        $this->app      = new Container();
        $this->document = new Document($this->app);
        $this->stub     = new Reader($this->document);
    }

    public function parseFromUrl($url){
        return $this->xml = $this->stub->load($url);
    }

    public function countStudents(){
        $students_q = 0;
        foreach($this->xml->getContent() as $d){
            foreach($d->students->student as $student){
                $students_q++;
            }
        }

        return $students_q;
    }


    static public function putGradesInXml($obj,$name)
    {
        foreach ($obj->getContent() as $d) {
            $module = GradesFiles::where('ModuleVariantID',$d->modulevariantid)->get()->last();
            foreach ($d->students->student as $student) {
                $examGrade = Grades::where('id_student','=',$student->id,' and ','grade_file_id','=',$module->ModuleVariantID)->get()->last()->exam_grade;
                $consultingGrades = ConsultingGrades::where('id_student','=',$student->id,' and ','id_num_plan','=',$module->ModuleVariantID)->get()->last();
                if(isset($examGrade)){
                    $student->credits_test = $examGrade+(isset($consultingGrades->grade_consulting)?$consultingGrades->grade_consulting:0);
                }
            }
        }
        $obj->setContent($d);
        return $obj->getContent()->asXml(public_path() . DIRECTORY_SEPARATOR."tmp".DIRECTORY_SEPARATOR."XML".DIRECTORY_SEPARATOR.$name);
    }

}
