<?php

/**
 * Excel操作类（扩展版）
 * User: Shixin.Ma <744857373@qq.com>
 * Date: 2015/11/12
 *
 * 示例：
 *   $Excel = new Excel();
 *   $Excel->excel->letter = 'ABCDEFGH';
 *   $Excel->excel->setAllColsWidth('20');
 *   $Excel->excel->setMultilineCellcaption($multilineCellcaption);
 *   //设置Excel sheet名称
 *   $Excel->excel->addSheetName(array('示例sheet'));
 *   //设置多行标题
 *   $Excel->excel->cellCaptionRows = '2';
 *   $Excel->excel->setAllCellvalue($cellvalue);
 *   //设置单元格行数
 *   $Excel->excel->cellContentRows = $cellContentRows;
 *   //设置Excel样式
 *   $Excel->excel->setAllHorizontal('center');  //设置水平居中
 *   $Excel->excel->setAllVertical('center');  //设置垂直居中
 *   $Excel->excel->mergeCells(['A1:A2','H1:H2', 'B1:C1', 'D1:E1', 'F1:G1']); //合并单元格
 *   $Excel->excel->setAllBorderStyle('medium'); //设置边框样式
 *   $Excel->excel->setAllBorderRGB('c0c0c0'); //设置边框颜色
 *   $Excel->excel->setfullRGB($fulldata, '91d7d9');
 *   $Excel->excel->exportExcel('表名示例');
 *
 ************************************************/
class Excel
{

    private $phpExcel;
    public $letter = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; //列标识
    public $cellCaptionRows; //单元格标题行数
    public $cellContentRows; //单元格内容行数

    /**
     * 构造方法，初始化PHPExcel类
     */
    public function __construct()
    {
        include_once __DIR__ . '/PHPExcel.php';
        $this->phpExcel = new PHPExcel();
    }

    /**
     * 扩展自定义设置
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->phpExcel->$key = $value;
    }

    public function __get($key)
    {
        return $this->phpExcel->$key;
    }

    //设置创建人
    public function setCreator($author)
    {
        $this->phpExcel->getProperties()->setCreator($author);
    }

    //设置最后修改人
    public function setLastModifiedBy($author)
    {
        $this->phpExcel->getProperties()->setLastModifiedBy($author);
    }

    //设置标题
    public function setTitle($title)
    {
        $this->phpExcel->getProperties()->setTitle($title);
    }

    //设置标题
    public function setSubject($subject)
    {
        $this->phpExcel->getProperties()->setSubject($subject);
    }

    //设置描述信息
    public function setDescription($description)
    {
        $this->phpExcel->getProperties()->setDescription($description);
    }

    //设置关键字
    public function setKeywords($keywords)
    {
        $this->phpExcel->getProperties()->setKeywords($keywords);
    }

    /**
     * 整体设置phpExcel类
     * $letter
     */
    //设置单行标题
    public function setUnilineCellcaption($data)
    {
        foreach ($data as $k => $v) {
            $cols = $this->letter[$k] . '1';
            $this->phpExcel->getActiveSheet()->setcellvalue($cols, $v);
        }
    }

    //设置多行标题
    public function setMultilineCellcaption($data)
    {
        foreach ($data as $k => $v) {
            $this->phpExcel->getActiveSheet()->setcellvalue($k, $v);
        }
    }

    //设置所有单元数据
    public function setAllCellvalue($data)
    {
        foreach ($data as $k => $v) {
            foreach ($v as $k2 => $v2) {
                $cols = $this->letter[$k2] . ($k + $this->cellCaptionRows + 1);
                $this->phpExcel->getActiveSheet()->setcellvalue($cols, $v2);
            }
        }
    }

    //设置列宽
    public function setAllColsWidth($colsWidth)
    {
        for ($i = 0; $i < strlen($this->letter); $i++) {
            $this->phpExcel->getActiveSheet()->getColumnDimension($this->letter[$i])->setWidth($colsWidth);
        }
    }

    /**
     * 设置水平位置
     * $position 水平位置参数
     *     general  一般位置
     *     left 居左显示
     *     right 居右显示
     *     center 居中显示
     *     centerContinuous 连续居中
     *     justify 两端对齐
     * @return PHPExcel_Style_Alignment
     */
    public function setAllHorizontal($position = 'center')
    {
        $cellRows = $this->cellCaptionRows + $this->cellContentRows;
        for ($i = 0; $i < strlen($this->letter); $i++) {
            for ($j = 1; $j < $cellRows; $j++) {
                $getAlignment = $this->phpExcel->getActiveSheet()->getStyle($this->letter[$i] . $j)->getAlignment();
                $getAlignment->setHorizontal($position);
            }
        }
    }

    /**
     * 设置垂直居中
     * @param $position 垂直位置参数
     * @return PHPExcel_Style_Alignment
     */
    public function setAllVertical($position = 'center')
    {
        $cellRows = $this->cellCaptionRows + $this->cellContentRows;
        for ($i = 0; $i < strlen($this->letter); $i++) {
            for ($j = 1; $j < $cellRows; $j++) {
                $getAlignment = $this->phpExcel->getActiveSheet()->getStyle($this->letter[$i] . $j)->getAlignment();
                $getAlignment->setVertical($position);
            }
        }
    }

    /**
     * 设置边框样式
     * $borderStyle 样式选项
     *    none        没有样式
     *    dashDot     单点虚线
     *    dashDotDot  双点虚线
     *    dashed      虚线
     *    dotted      点线式边框
     *    double      双线
     *    hair        最细线
     *    medium      中等线
     *    mediumDashDot 中等虚线
     *    mediumDashDotDot 中等双虚线
     *    mediumDashed    中等 点划线
     *    slantDashDot    斜虚线
     *    thick           厚实线
     *    thin            细线
     * @return PHPExcel_Style_Border
     */
    public function setAllBorderStyle($borderStyle = 'none')
    {
        $cellRows = $this->cellCaptionRows + $this->cellContentRows;
        for ($i = 0; $i < strlen($this->letter); $i++) {
            for ($j = 1; $j < $cellRows; $j++) {
                $getBorders = $this->phpExcel->getActiveSheet()->getStyle($this->letter[$i] . $j)->getBorders();
                $getBorders->getTop()->setBorderStyle($borderStyle);
                $getBorders->getLeft()->setBorderStyle($borderStyle);
                $getBorders->getRight()->setBorderStyle($borderStyle);
                $getBorders->getBottom()->setBorderStyle($borderStyle);
            }
        }
    }

    /**
     * 设置单元格边框颜色
     * @param string $color 颜色值
     * @return PHPExcel_Style_Border
     */
    public function setAllBorderRGB($color)
    {
        $cellRows = $this->cellCaptionRows + $this->cellContentRows;
        for ($i = 0; $i < strlen($this->letter); $i++) {
            for ($j = 1; $j < $cellRows; $j++) {
                $getBorders = $this->phpExcel->getActiveSheet()->getStyle($this->letter[$i] . $j)->getBorders();
                $getBorders->getTop()->getColor()->setRGB($color);
                $getBorders->getLeft()->getColor()->setRGB($color);
                $getBorders->getRight()->getColor()->setRGB($color);
                $getBorders->getBottom()->getColor()->setRGB($color);
            }
        }
    }

    /**
     * 设置单元格填充颜色
     * @param string $color 颜色值
     * @return PHPExcel_Style_Border
     */
    public function setAllFillRGB($color)
    {
        $cellRows = $this->cellCaptionRows + $this->cellContentRows;
        for ($i = 0; $i < strlen($this->letter); $i++) {
            for ($j = 1; $j < $cellRows; $j++) {
                $getFill = $this->phpExcel->getActiveSheet()->getStyle($this->letter[$i] . $j)->getFill();
                $getFill->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                $getFill->getStartColor()->setRGB($color);
            }
        }
    }

    //添加sheet
    public function addSheet($index)
    {
        $objActSheet = ($index == 0) ? $this->phpExcel->getActiveSheet() : $this->phpExcel->createSheet();
        $this->phpExcel->setActiveSheetIndex($index);
    }

    //添加sheet的名称
    public function addSheetName($data)
    {
        foreach ($data as $k => $v) {
            $this->phpExcel->getActiveSheet()->setTitle($v);
        }
    }

    /**
     * 合并单元格
     * @param $pRanges 单元格范围 [(e.g. A1:E1)]
     * @return PHPExcel_Worksheet
     */
    public function mergeCells($pRanges)
    {
        foreach ($pRanges as $pRange) {
            $this->phpExcel->getActiveSheet()->mergeCells($pRange);
        }
    }

    /**
     * 分离单元格
     * @param $pRanges 单元格范围 [(e.g. A1:E1)]
     * @return PHPExcel_Worksheet
     */
    public function unmergeCells($pRanges)
    {
        foreach ($pRanges as $pRange) {
            $this->phpExcel->getActiveSheet()->unmergeCells($pRange);
        }
    }

    /**
     * 自定义单元格颜色
     * @param string $color 颜色值
     * @return PHPExcel_Style_Border
     */
    public function setfullRGB($pRanges, $color)
    {
        foreach ($pRanges as $value) {
            $getFill = $this->phpExcel->getActiveSheet()->getStyle($value)->getFill();
            $getFill->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $getFill->getStartColor()->setRGB($color);
        }
    }

    //保护单元格
    public function protectCells($pRanges)
    {
        $this->phpExcel->getActiveSheet()->getProtection()->setSheet(true);
        foreach ($pRanges as $value) {
            $this->phpExcel->getActiveSheet()->protectCells($value, 'PHPExcel');
        }
    }

    //获得phpExcel对象
    public function getphpExcelObj()
    {
        return $this->phpExcel;
    }

    //写入Excel
    public function writeExcel($pathFileName, $fileExt = 'xlsx')
    {
        $writer = PHPExcel_IOFactory::createWriter($this->phpExcel, 'Excel2007');
        $writer->save($pathFileName . '.' . $fileExt);
    }

    //导出Excel
    public function exportExcel($outputFileName, $fileExt = 'xlsx')
    {
        //兼容类型
        if ($fileExt == 'xlsx') {
            $this->dumpExcel2007($outputFileName . '.' . $fileExt);
        } else {
            $this->dumpExcel5($outputFileName . '.' . $fileExt);
        }
    }

    //输出Excel5格式
    public function dumpExcel5($outputFileName)
    {
        $objWriter = PHPExcel_IOFactory::createWriter($this->phpExcel, 'Excel5');
        $this->fileHeader($outputFileName);
        $objWriter->save('php://output');
    }

    //输出excel—2007格式
    public function dumpExcel2007($outputFileName)
    {
        $objWriter = PHPExcel_IOFactory::createWriter($this->phpExcel, 'Excel2007');
        $this->fileHeader($outputFileName);
        $objWriter->save('php://output');
    }

    //直接输出到浏览器
    private function fileHeader($outputFileName)
    {
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="' . $outputFileName . '"');
        header("Content-Transfer-Encoding: binary");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
    }

}