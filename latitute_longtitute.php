<?php

use App\Models\LayerObjects;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;


    function uploadbas64Photo($base64Img){
        $extension = explode('/', explode(':', substr($base64Img, 0, strpos($base64Img, ';')))[1])[1]; 
        $replace = substr($base64Img, 0, strpos($base64Img, ',')+1);
        $image = str_replace($replace, '', $base64Img); 
        $image = str_replace(' ', '+', $image);  
        $imageName = time().'.'.$extension;
        Storage::disk('public')->put($imageName, base64_decode($image));
        return $imageName;
    }
    function existLocation($branchArea,$location,$point){
        $branchId = "";
        $is_inside = false;
        foreach($branchArea as $branch){
            $b_area = json_decode($branch->branch_area);
            $is_point = false;
            foreach($b_area as $area){
                if($area == $location){
                    $is_point = true;
                    $branchId = $branch->id;
                    break;
                }
            }
           
            if ($is_point) return array($is_point, $branchId);
            if (inside($point,$b_area)){
                $is_inside = true;
                $branchId = $branch->id;
            }
            if ($is_inside) break;
        }
        return  array($is_inside, $branchId);
    }
    function inside($point, $fenceArea) {
 	
        $x = $point[0];
        $y = $point[1];
        $length = count($fenceArea) - 1;
        $inside = false;
        for ($i = 0, $j = $length; $i < $length; $j = $i++) {
            
            $l_area    = explode (",", $fenceArea[$i]);
            $long_area = explode (",", $fenceArea[$j]);
        
            $xi =$l_area[0] ; 
            $yi =$l_area[1];
        
            $xj = $long_area[0]; 
            $yj = $long_area[1];
            
            $intersect = (($yi > $y) != ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);
            if ($intersect) $inside = !$inside;
        }
    
        return $inside;
    }

    //distance wise branch
    function getBranch($branchArea,$latitute_to,$accepted_distance){
        $branchId = "";
        $is_inside = false;
        $min_distance = $distance = 0;
        foreach($branchArea as $index => $branch){
            $b_area = json_decode($branch->branch_area);
            foreach($b_area as $key => $area){
                $lattitute_from = explode (",", $area);
                if($key == 0){
                    $distance    = getDistance($lattitute_from,$latitute_to);
                }else{
                    $i_distance  =  getDistance($lattitute_from,$latitute_to);
                    if($i_distance < $distance){
                        $distance = $i_distance;
                    }
                }
            }
            if($index == 0){
                $min_distance = $distance;
            }else{
                if($distance < $min_distance){
                    $min_distance = $distance;
                    $branchId     = $branch->id;
                }
            }
        }
        if (($min_distance * 1000) <= ($accepted_distance)) $is_inside = true;
        $branch_id = ($is_inside) ? $branchId : null;
        return $branch_id;
    }
    function getDistance($lattitute_from,$latitute_to){
        $p = 0.017453292519943295;
        $a = 0.5 - cos(($latitute_to[0] - $lattitute_from[0]) * $p) / 2 + cos($lattitute_from[0] * $p) * cos($latitute_to[0] * $p) * (1 - cos(($latitute_to[1] - $lattitute_from[1]) * $p)) / 2;
        return number_format(12742 * asin(sqrt($a)),2);
    }
?>