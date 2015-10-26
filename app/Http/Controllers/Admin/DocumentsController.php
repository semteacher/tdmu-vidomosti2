<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Model\CreateDocuments\Documents;
use File;
use App\Model\CreateDocuments\Statistics;
use Illuminate\Support\Facades\Response;


class DocumentsController extends Controller
{

    public function __construct()
    {
        $this->middleware('role:Admin,Self-Admin,Inspektor');
        view()->share('type', 'documents');
    }

    /**
     * @param $idFileGrade
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function getAllDocuments($idFileGrade)
    {
        $doc = new Documents($idFileGrade);
        return redirect($doc->formDocuments());
    }



    /**
     *
     */
    public function getAllStatistics($idFileGrade)
    {
        $doc = new Statistics($idFileGrade);
        $data['general'] = $doc->formGeneralStat();
        $data['bk'] = $doc->formGeneralBKStat();
        $data['detailed'] = $doc->formDetailedStat();

        return view('admin.documents.allStatistics',compact('data','idFileGrade'));
    }


    /**
     * @param $name
     */
    public function downloadStatistics($name,$idFileGrade){
        $doc = new Statistics($idFileGrade);
        File::makeDirectory(public_path() . '\tmp', 0775, true, true);
        switch($name){
            case "general":
                File::put(public_path().'\tmp\formGeneralStat.doc', $doc->formGeneralStat()['body']);
                return '\tmp\formGeneralStat.doc';
                break;
            case "bk":
                File::put(public_path().'\tmp\formGeneralBKStat.doc', $doc->formGeneralBKStat()['body']);
                return '\tmp\formGeneralBKStat.doc';
                break;
            case "detailed":
                File::put(public_path().'\tmp\formDetailedStat.doc', $doc->formDetailedStat()['body']);
                return '\tmp\formDetailedStat.doc';
                break;
        }
    }


}