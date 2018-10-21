<?php
// graph data settings
$initialPrice = $price = 150;
$itertions = 150;
$priceChangeFactor = 10;
$priceFluctuation = 5;
$dateString = '2017-01-01 00:00:00';
$values = [];

echo 'Simulation start: ' . $dateString . PHP_EOL;

// build random values
for ($i = 0; $i < $itertions; $i++) {
    $time = date('Y-m-d H:i:s', strtotime($dateString . ' +15min'));
    $open = $price;
    $close = $price + rand(-$priceChangeFactor, $priceChangeFactor);
    $high = $open < $close ? $close + rand(0, $priceFluctuation) : $open + rand(0, $priceFluctuation);
    $low = $open < $close ? $open - rand(0, $priceFluctuation) : $close - rand(0, $priceFluctuation);
    $values[] = [
        'time' => $time,
        'open' => $open,
        'high' => $high,
        'low' => $low,
        'average' => round(($high + $low) / 2),
        'close' => $close,
        'extreme' => null,
    ];
    $price = $close;
    $dateString = $time;
}

echo 'Simulation end: ' . $dateString . PHP_EOL;

//var_dump($values);exit;

// find local edge values and draw a chart
$range = 10;
$initialIndent = 100;
$display = true;
$edgeValues = [];
foreach ($values as $key => $value) {
    $scoreMax = 0;
    $scoreMin = 0;
    for ($i = -$range; $i <= $range; $i++) {
        // not enough adjoining data
        if (!isset($values[$key + $i])) {
            continue 2;
        }
        // local max
        if ($values[$key + $i]['high'] <= $value['high']) {
            $scoreMax++;
        }
        // local min
        if ($values[$key + $i]['low'] >= $value['low']) {
            $scoreMin++;
        }
    }
    // draw a graph 
    if ($display) {
        $ratioLow = ($value['low'] - $initialPrice) / $initialPrice;
        $ratioHigh = ($value['high'] - $initialPrice) / $initialPrice;

        $indentChangeLow = round($ratioLow * $initialIndent);
        $indentChangeHigh = round($ratioHigh * $initialIndent);
        for ($i = 1; $i < $initialIndent + $indentChangeLow; $i++) {
            echo ' ';
        }
        $price_bar = "";
        for ($i = $i; $i <= $initialIndent + $indentChangeHigh; $i++) {
            $price_bar .= '=';
        }
        $price_bar = substr($price_bar, 1, -1);
        echo '|' . $price_bar . '|';
        echo '       ';
        echo $value['average'];
    }

    // mark and store edge values
    if ($scoreMax === 2 * $range + 1) {
        if ($display) {
            echo ' || LOCAL MAX ';
            echo $value['high'];
        }
        $edgeValues[] = $value;
        $values[$key]['extreme'] = 'localMax';
    }
    if ($scoreMin === 2 * $range + 1) {
        if ($display) {
            echo ' || LOCAL MIN ';
            echo $value['low'];
        }
        $edgeValues[] = $value;
        $values[$key]['extreme'] = 'localMin';
    }
    if ($display) {
//        echo ' || ' . $value['time'];
        echo PHP_EOL;
    }
}

//var_dump($values);exit;

// simulate transaction ratios
$ratioValues = [0.1, 0.33, 0.5, 0.75, 1, 1.25, 1.5, 1.75, 2, 2.25, 2.5, 2.75, 3, 3.25, 3.5, 3.75, 4, 10];
$spread = 3;
$size = 20;
$ratios = [];
foreach ($ratioValues as $ratioValue) {
    $index = count($ratios);
    $ratios += array_fill($index, 1000, $ratioValue);
}
$results = [];
$initialWallet = 1000;
echo 'Initial wallet: ' . $initialWallet . PHP_EOL;
foreach ($ratios as $ratio) {
    $trade = false;
    $direction = 0;
    $wallet = $walletMin = $walletMax = $initialWallet;
    $lastLocalLow = $previousLocalLow = null;
    $lastLocalHigh = $previousLocalHigh = null;
    foreach ($values as $key => $value) {
        // remember wallet min and max
        if ($walletMin > $wallet) {
            $walletMin = $wallet;
        }
        if ($walletMax < $wallet) {
            $walletMax = $wallet;
        }

        if (!$trade) {
            $direction = rand(0,1);
            // buy
            if ($direction) {
                $trade = $value['average'] + $spread;
            // sell
            } else {
                $trade = $value['average'] - $spread;
            }
            continue;
        }

        // check if price moved and closed transaction with profit or loss
        if ($direction) {
            if ($value['high'] > $trade + ($ratio * $size)) {
                $wallet += $size * $ratio;
                $trade = false;
            } elseif ($value['low'] < $trade - $size) {
                $wallet -= $size;
                $trade = false;
            }
        } else {
            if ($value['high'] > $trade + $size) {
                $wallet -= $size;
                $trade = false;
            } elseif ($value['low'] < $trade - ($ratio * $size)) {
                $wallet += $size * $ratio;
                $trade = false;
            }
        }
    }
    $results['ratio_' . $ratio]['final'][] = $wallet;
    $results['ratio_' . $ratio]['min'][] = $walletMin;
    $results['ratio_' . $ratio]['max'][] = $walletMax;
}

echo PHP_EOL;
echo '======================' . PHP_EOL;
echo '==SIMULATION RESULTS==' . PHP_EOL;
echo '======================' . PHP_EOL;
echo PHP_EOL;
foreach ($results as $ratio => $result) {
    $averageFinal = array_sum($result['final']) / count($result['final']);
    $averageMin = array_sum($result['min']) / count($result['min']);
    $averageMax = array_sum($result['max']) / count($result['max']);
    echo 'For ' . $ratio . ' average final: ' . $averageFinal . ' average min: ' . $averageMin . ' average max: ' . $averageMax . PHP_EOL;
}
