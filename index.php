

<!-- Для отправки POST-запроса используйте FormData. Key: comicsId[];  Value: Id комикса
    Данные POST-запроса принимаются как $_POST['comicsId']  -->

<!-- POST-запрос возвращает JSON-массив с ответом и HTML-код с таблицей-->
<?php
//Функция с работой с CURL для получения json с id, датой создания и курсом доллара
function get_dollar_from_comics_id ($id) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://xkcd.com/'.$id.'/info.0.json');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);


    $xkcd_response = curl_exec($ch);
    $comics_data = json_decode($xkcd_response, true);
    
    $comics_create_date = ($comics_data['day']>9 ? $comics_data['day'] : '0'.$comics_data['day']).'/'.($comics_data['month']>9 ? $comics_data['month'] : '0'.$comics_data['month']).'/'.$comics_data['year'];

    $russia_bank_url_api = 'https://www.cbr.ru/scripts/XML_daily.asp?date_req='. $comics_create_date;
    
    curl_setopt($ch, CURLOPT_URL, $russia_bank_url_api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $russia_bank_response = curl_exec($ch);
    $xml_data = simplexml_load_string($russia_bank_response);

    $result = [];
    foreach($xml_data->Valute as $valute) {
        if($valute->CharCode == 'USD'){
            $dollarVunitRate = floatval(str_replace(',','.',$valute->Value));
            $result['ID'] = $id;
            $result['DATE'] = str_replace('/','.',$comics_create_date);
            $result['USD'] = $dollarVunitRate;
        }
    }

    curl_close($ch);

    return $result;
}


?>
<?php 
//Обработка входящих запросов
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    
    $comics_id = !empty($_POST['comicsId']) ? $_POST['comicsId'] : [];

    if(is_string($comics_id)){
        $comics_id = array_filter(array_map('intval',array_map('trim',explode(',',$comics_id))));
    } else if (is_array($comics_id)){
        $comics_id = array_filter(array_map('intval',$comics_id));
    }
    $json_table_rows = [];
    foreach ($comics_id as $id) {
        $json_table_rows[] = get_dollar_from_comics_id($id);
    }
    echo json_encode($json_table_rows); 
    // exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <title>Web Service</title>
</head>
<body>
    <table class="table">
        <thead>
            <tr>
            <th scope="col">ID комикса</th>
            <th scope="col">Дата выхода</th>
            <th scope="col">Курс рубля</th>
            </tr>
        </thead>
        <tbody id="table-body">
        <?php if(!empty($json_table_rows)): ?>
            <?php foreach ($json_table_rows as $row ): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['ID']); ?></td>
                    <td><?php echo htmlspecialchars($row['DATE']); ?></td>
                    <td><?php echo htmlspecialchars(1/$row['USD']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    <script>
        //Для отправки POST-запроса внутри кода----------------------------------------------------------------------------------------
        //Функция создания строки в таблице
        function insertRowInTable(data) {
            const tableBody = document.getElementById('table-body');

            const row = document.createElement('tr');

            const idColumn = document.createElement('td');
            const dateColumn = document.createElement('td');
            const RUBColumn = document.createElement('td');

            idColumn.textContent = data.ID;
            dateColumn.textContent = data.DATE;
            RUBColumn.textContent = 1/data.USD;

            row.appendChild(idColumn);
            row.appendChild(dateColumn);
            row.appendChild(RUBColumn);
            
            tableBody.appendChild(row);

        }
        //Отправка POST запроса
        async function getData(comicsId) {
            const url = "http://localhost/2_%D1%82%D0%B5%D1%81%D1%82%D0%BE%D0%B2%D0%BE%D0%B5/index.php";
            const formData = new FormData();
            comicsId.forEach(id => formData.append('comicsId[]', String(id)));
            const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
            const result = await response.json();
            const parsedResult = result.map(item => JSON.parse(item));

            console.log(parsedResult);
            localStorage.
            parsedResult.forEach(row => insertRowInTable(row));

        }
        //Сюда вводить ID комиксов и раскомментировать функцию---------------------------------------------------------------------------
        const comicsId = [100, 614, 512];
        //getData(comicsId);

    </script>
</body>
</html>
