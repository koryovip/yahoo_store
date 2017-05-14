const request = require('request');
const cheerio = require('cheerio');
//const async = require('async');
// https://github.com/mapbox/node-sqlite3
const sqlite3 = require('sqlite3').verbose();
const db = new sqlite3.Database(':memory:');

/*
db.serialize(function() {
  db.run("CREATE TABLE items (url TEXT PRIMARY KEY NOT NULL, img TEXT NOT NULL, name TEXT NOT NULL, price TEXT NOT NULL)");

  var stmt = db.prepare("INSERT INTO items VALUES (?,?,?,?)");
  for (var i = 0; i < 10; i++) {
      stmt.run("Ipsum " + i,"Ipsum " + i,"Ipsum " + i,"Ipsum " + i);
  }
  stmt.finalize();

  db.each("SELECT rowid AS id, url FROM items", function(err, row) {
      console.log(row.id + ": " + row.url);
  });
});
db.close();
*/

//ヘッダーを定義
var headers = {
  'Content-Type': 'text/html; charset=UTF-8'
}

var options = {
	url: 'https://www.mercari.com/jp/search/?keyword=%E3%83%9F%E3%82%AD%E3%83%8F%E3%82%A6%E3%82%B9',
	headers: headers,
	json: false
};

var getItemList = function(callback) {
	var items_new = [];
	request.get(options, function (error, response, body) {
	 	if (!(!error && response.statusCode == 200)) {
			console.log('error: '+ response.statusCode);
			return callback(items_new);
		}
		// console.log(body);
		const $ = cheerio.load(body);
		var result = $('body > div > main > div.l-content > section > div > section > a');
		//console.log(result.length);
		result.each(function(index, elem) {
			var row = $(this);
			var item = {};
			item.url = row.attr('href');
			item.img = row.children('figure').children('img').attr('data-src');
			item.name = row.children('div.items-box-body').children('h3.items-box-name').text();
			item.price = row.children('div.items-box-body').children('div.items-box-num').children('.items-box-price').text();
			items_new.push(item);
		});
		//console.log(items_new.length);
		//console.log('------items_new---------------------------------------------');
		//items_new.forEach(function(item){
		//	console.log(item.name);
		//});
		//console.log('---------------------------------------------------');
		return callback(items_new);
	});
};

var insertData = function(stmt1, stmt2, item_new) {
	return new Promise(function (resolve, reject) {
		// console.log(item_new.url);
		stmt1.get(item_new.url, function(err, row) {
			if (err != null) {
                reject(err);
                return;
            }
			// console.log(row);
			if (row.cnt > 0) {
				console.log('already exist!');
				return;
			}
			//console.log('1');
			stmt2.run(item_new.url, item_new.img, item_new.name, item_new.price);
			//console.log('2');
			resolve('OK');
		});
	});
};

getItemList(function(items_new){
	// console.log(items_new.length);
	db.serialize(function() {
		db.run('CREATE TABLE items (url TEXT PRIMARY KEY NOT NULL, img TEXT NOT NULL, name TEXT NOT NULL, price TEXT NOT NULL)');
	 	var stmt1 = db.prepare('SELECT count(*) cnt FROM items where url=?');
	 	var stmt2 = db.prepare('INSERT INTO items VALUES (?,?,?,?)');
	 	var promises = [];
	 	items_new.forEach(function (item_new, index_new, array_new) {
	 		promises.push(insertData(stmt1, stmt2, item_new));
	 	});
	 	Promise.all(promises).then(function(results) {
			stmt1.finalize();
			stmt2.finalize();
			db.get('SELECT count(*) cnt FROM items', function(err, row){
				console.log('db row count:' + row.cnt);
			});
	 	}).catch(function() {
			console.log('error');
		});
	});
})

/*
setInterval(function() {
	request.get(options, function (error, response, body) {
	 	if (!(!error && response.statusCode == 200)) {
			console.log('error: '+ response.statusCode);
			return;
		}
		// console.log(body);
		const $ = cheerio.load(body);
		var result = $('body > div > main > div.l-content > section > div > section > a');
		// console.log(result);
		items_new = [];
		result.each(function(index, elem) {
			var row = $(this);
			var item = {};
			item.url = row.attr('href');
			item.img = row.children('figure').children('img').attr('data-src');
			item.name = row.children('div.items-box-body').children('h3.items-box-name').text();
			item.price = row.children('div.items-box-body').children('div.items-box-num').children('.items-box-price').text();
			items_new.push(item);
		});
		console.log(new Date());
		//items_new.forEach(function (item_new, index_new, array_new) {
		//	console.log(item_new.name);
		//});
		if (items_old.length <= 0) {
			items_old = items_new;
			console.log('set old');
		} else {
			var diff = [];
			items_new.forEach(function (item_new, index_new, array_new) {
				var exits = false;
				for (var iii = 0, count = items_old.length; iii < count; iii++) {
					if (item_new.url === items_old[iii].url) {
						exits = true;
						break;
					}
				}
				if (!exits) {
					diff.push(item_new);
				}
			});
			console.log(diff);
			//console.log(items_new[0].name);
			//console.log(items_old[0].name);
			items_old = items_new;
			//items_new = [];
		}
	});
}, 3000);
*/