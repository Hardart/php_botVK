<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/uikit/3.6.10/css/uikit-core.min.css">
	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link href="https://fonts.googleapis.com/css2?family=Lora:wght@700&display=swap" rel="stylesheet">
	<style>
		h1,
		h2 {
			font-family: 'Lora', serif
		}

		.uk-input:disabled,
		.uk-select:disabled,
		.uk-textarea:disabled {
			color: #000
		}
	</style>
	<title>Отправка данных боту</title>
</head>

<body>
	<div class="uk-container" uk-scrollspy="cls: uk-animation-fade; target: .uk-section; delay: 100; repeat: false">
		<div class="uk-section uk-section-muted uk-text-center">
			<h1>Загрузка файла с данными</h1>
			<h2 class="uk-margin-remove-top">(Логин, Пароль, Welcome-код)</h2>
			<div class="uk-child-width-1-3 uk-grid uk-flex-center" uk-grid>
				<div>
					<div class="uk-card uk-card-default">
						<form action="" method="post" enctype="multipart/form-data">
							<div class="uk-flex uk-flex-between" uk-margin>
								<div uk-form-custom="target: true">
									<input type="file" id="file">
									<input class="uk-input uk-form-width-medium" type="text" placeholder="Выберите файл" disabled>
								</div>
								<button type="submit" class="uk-button uk-button-danger" disabled>Загрузить</button>
							</div>
						</form>
						<div class="uk-hidden file_upload">
							<h3 class="uk-margin-remove">Загрузка файла прошла успешно</h3>
							<button class="uk-button uk-button-primary uk-margin-top upload_again">Загрузить снова</button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="uk-section uk-section-muted uk-margin-top uk-padding-small uk-flex uk-flex-center uk-hidden">
			<table class="uk-table uk-table-small uk-width-3-4 uk-text-center" uk-scrollspy="cls: uk-animation-fade; target: tr; delay: 150; repeat: false">
				<thead>
					<tr>
						<th class="uk-table-expand uk-text-center">Login</th>
						<th class="uk-table-expand uk-text-center">Password</th>
						<th class="uk-table-expand uk-text-center">W_code</th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
	<script src='https://cdnjs.cloudflare.com/ajax/libs/uikit/3.6.10/js/uikit.min.js'></script>
	<script src='src/script.js'></script>
</body>

</html>