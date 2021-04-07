const form = document.querySelector('form')
const loginForm = document.querySelector('form.login')
const submit = document.querySelector('form button')
const file = document.querySelector('#file')
const login = document.querySelector('#login')
const pass = document.querySelector('#pass')
const btn = document.querySelector('.upload_again')
const upload = document.querySelector('.file_upload')
const table = document.querySelector('table')
const sections = document.querySelectorAll('.uk-section')
const type = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"


function sendRequest(method, url, body) {
	return fetch(url, {
		method: method,
		body: body
	}).then(data => {
		if (data.statusText == 'OK') {
			form.classList.toggle('uk-hidden')
			upload.classList.toggle('uk-hidden')
			form.reset()
			submit.setAttribute('disabled', '')
		} else {
			console.log(data.statusText);
		}
		return data.json()
	})
}

file.onclick = () => file.setAttribute('accept', '.xlsx')

file.onchange = () => {
	if (file.files[0] && file.files[0].type == type) {
		let name = file.files[0].name
		submit.removeAttribute('disabled')
	} else {
		submit.setAttribute('disabled', '')
	}
	// 
}

btn.onclick = () => {
	form.classList.toggle('uk-hidden')
	upload.classList.toggle('uk-hidden')
	sections[1].classList.add('uk-hidden')
	table.removeChild(table.children[1])
}

form.onsubmit = e => {
	e.preventDefault()
	let endpoint = "http://robb-i.ru/php_bot/upload_data.php"
	let fd = new FormData()
	fd.append("sheet", file.files[0])

	sendRequest('post', endpoint, fd)
		.then(data => {
			sections[1].classList.remove('uk-hidden')
			const tBody = table.appendChild(document.createElement('tbody'))
			for (let i in data) {
				const tr = tBody.appendChild(document.createElement("tr"))
				const login = document.createElement("td")
				const pass = document.createElement("td")
				const wCode = document.createElement("td")
				tr.appendChild(login).innerHTML = data[i].login
				tr.appendChild(pass).innerHTML = data[i].password
				tr.appendChild(wCode).innerHTML = data[i].w_code
			}
		})
		.catch(err => console.log(err))
}

loginForm.onsubmit = e => {
	refLogin = "Ren"
	refPass = "aaa"
	if (login.value == refLogin && pass.value == refPass) {
	} else {
		e.preventDefault()
	}
}