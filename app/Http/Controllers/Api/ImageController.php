<?php

namespace App\Http\Controllers\Api;

use App\Models\Image;
use Illuminate\Http\Request;
use App\Helper\ResponseHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Validator;

class ImageController extends Controller
{
    public function upload(Request $request)
    {
        try {
           $validator = Validator::make($request->all(), [
                'file' => 'required|file|max:10240', // Accepts all file types
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error(message: $validator->errors()->first(), statusCode: 422, status: 0);
            }

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $ext = $file->getClientOriginalExtension();
                $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $fileName = $originalFileName . '_' . time() . '.' . $ext;

                $folder = 'images';
                $filePath = $folder . '/' . $fileName;
                $file->move($folder, $fileName);

                $fullFileUrl = $filePath ? url($filePath) : null;

                $imageModel = new Image();
                $imageModel->filename = $fileName;
                $imageModel->path = $fullFileUrl;
                $imageModel->save();

                return ResponseHelper::success(
                    message: 'Image uploaded successfully!',
                    data: $imageModel,
                    statusCode: 201,
                    status: 1
                );
            } else {
                return ResponseHelper::error(message: 'No file was uploaded.', statusCode: 400, status: 0);
            }
        } catch (Exception $e) {
            Log::error('File Upload Failed: ' . $e->getMessage() . ' - Line no. ' . $e->getLine());
            return ResponseHelper::error(message: 'Unable to upload file! Please try again.', statusCode: 500, status: 0);
        }
    }
}
