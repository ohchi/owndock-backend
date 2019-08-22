<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Yaml;
use josegonzalez\Dotenv\Loader as Dotenv;
use \wapmorgan\UnifiedArchive\UnifiedArchive;
use App\Exceptions\AppException;

class LaradockController extends Controller
{

    public function read(Request $request)
    {
        return [
            'env' => self::getEnvExample(),
            'compose' => self::getLaradockCompose()
        ];
    }

    public function download(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $archType = array_key_exists('arch_type', $data) ? $data['arch_type'] : 'zip';

        $pathToFile = self::createProjectOnFs($data['compose'], $data['env'], $archType);

        return response()->download($pathToFile, 'owndock.'.$archType)->deleteFileAfterSend(true);
    }

    private static function getLaradockCompose()
    {
        $contents = Storage::disk('laradock')->get('docker-compose.yml');
        return Yaml::parse($contents);
    }

    private static function getEnvExample()
    {
        $disks = config('filesystems.disks');
        $laradockRoot = $disks['laradock']['root'];
        $dotenv = new Dotenv($laradockRoot.'/env-example');
        $arr = $dotenv->parse()->toArray();
        
        return $arr;
    }

    private static function createProjectOnFs($compose, $env, $archType)
    {
        if ($archType != 'zip' &&
            $archType != 'tar.gz'
        ) throw new AppException(400);

        $projectId = uniqid();
        $disk = Storage::disk('owndock');

        $disk->makeDirectory($projectId);

        // Create docker-compose.yml
        $composeYaml = Yaml::dump($compose, 100, 2);
        $disk->put("$projectId/docker-compose.yml", $composeYaml);

        // Create .env
        $disk->put("$projectId/example.env", self::makeDotEnvString($env));

        $owndockRoot = config('filesystems.disks.owndock.root');
        $laradockRoot = config('filesystems.disks.laradock.root');
        $projectDir = "$owndockRoot/$projectId";

        // Copy service dirs
        $services = $compose['services'];
        foreach ($services as &$s) {
            
            if (!array_key_exists('build', $s)) continue;
            $build = $s['build'];

            // if (gettype($build) != 'array') continue;

            $buildType = gettype($build);

            if ($buildType == 'array') {

                if (array_key_exists('context', $build)) {
                    $context = $build['context'];
                    File::copyDirectory("$laradockRoot/$context", "$projectDir/$context");
                }

            } else if ($buildType == 'string') {

                File::copyDirectory("$laradockRoot/$build", "$projectDir/$build");
            }
        }

        $pathToFile = "$owndockRoot/$projectId.$archType";

        // Create an archive
        UnifiedArchive::archiveFiles([$projectDir => 'owndock'], $pathToFile);

        // Delete project folder
        File::deleteDirectory($projectDir);

        return $pathToFile;
    }

    private static function makeDotEnvString($env)
    {
        $str = '';

        foreach ($env as $key => $value) {
            $valueStr = is_bool($value) ? $value ? 'true' : 'false' : $value;
            $str = $str."\n$key=$valueStr";
        }

        return $str;
    }
}
