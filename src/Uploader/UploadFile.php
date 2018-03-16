<?php namespace Stevenyangecho\UEditor\Uploader;

use Stevenyangecho\UEditor\Uploader\Upload;

use App\Services\OSS;
use DateTime;

/**
 *
 *
 * Class UploadFile
 *
 * 文件/图像普通上传
 *
 * @package Stevenyangecho\UEditor\Uploader
 */
class UploadFile  extends Upload{
    use UploadQiniu;
    public function doUpload()
    {


        $file = $this->request->file($this->fileField);
        if (empty($file)) {
            $this->stateInfo = $this->getStateInfo("ERROR_FILE_NOT_FOUND");
            return false;
        }
        if (!$file->isValid()) {
            $this->stateInfo = $this->getStateInfo($file->getError());
            return false;

        }

        $this->file = $file;

        $this->oriName = $this->file->getClientOriginalName();

        $this->fileSize = $this->file->getSize();
        $this->fileType = $this->getFileExt();

        $this->fullName = $this->getFullName();


        $this->filePath = $this->getFilePath();

        $this->fileName = basename($this->filePath);


        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
            return false;
        }
        //检查是否不允许的文件格式
        if (!$this->checkType()) {
            $this->stateInfo = $this->getStateInfo("ERROR_TYPE_NOT_ALLOWED");
            return false;
        }

        if(config('UEditorUpload.core.mode')=='local'){
            try {
                $this->file->move(dirname($this->filePath), $this->fileName);

                $this->stateInfo = $this->stateMap[0];

            } catch (FileException $exception) {
                $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
                return false;
            }

        }else if(config('UEditorUpload.core.mode')=='qiniu'){

            $content=file_get_contents($this->file->getPathname());
            return $this->uploadQiniu($this->filePath,$content);

            //阿里云oss
        }else if(config('UEditorUpload.core.mode')=='oss'){

            try {
                // $this->file->move(dirname($this->filePath), $this->fileName);
                // $ossClient = new OssClient(ALIOSS_ACCESSKEYID, ALIOSS_ACCESSKEYSECRET, ALIOSS_ENDPOINT, true);
                // //获得文件类型
                $type='.'.$this->file->getClientOriginalExtension();
                $this->fileType=$type;//设置UEditor的文件类型
                //生成随机文件名
                $object='uploads/ueditor/'.date('Y-m-d',time()).'/'.time() . str_random(10);
                $object=$object.$type;//拼接到后戳名的文件名
                $this->fullName=$object;//设置UEditor的文件名
                try{
                   //上传文件
                   OSS::publicUpload(config('oss.bucketName'), $object, $this->file->getRealPath(), ['ContentType' => $this->file->getMimeType()]);
                   // $ossClient->uploadFile(ALIOSS_BUCKET,$object,$this->file->getPathName());
                }catch (OssException $e){
                    //设置错误消息为未知错误
                   $this->stateInfo = $this->stateMap[14];
                   return false;
                }

                $this->stateInfo = $this->stateMap[0];

            } catch (FileException $exception) {
                $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
                return false;
            }

        }else{
            $this->stateInfo = $this->getStateInfo("ERROR_UNKNOWN_MODE");
            return false;
        }




        return true;

    }
}
