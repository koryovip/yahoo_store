const app = require('express')();
const http = require('http').Server(app);
const io = require('socket.io')(http);
const request = require('request');
const cheerio = require('cheerio');
const sqlite3 = require('sqlite3').verbose();
const db = new sqlite3.Database(':memory:'); // https://github.com/mapbox/node-sqlite3

const headers = {
	'Content-Type': 'text/html; charset=UTF-8'
};

const options = {
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
		//console.log(body);
		const $ = cheerio.load(body);
		var result = $('body > div > main > div.l-content > section > div > section > a');
		result.each(function(index, elem) {
			var row = $(this);
			var item = {};
			item.url = row.attr('href');
			item.img = row.children('figure').children('img').attr('data-src');
			item.name = row.children('div.items-box-body').children('h3.items-box-name').text();
			item.price = row.children('div.items-box-body').children('div.items-box-num').children('.items-box-price').text();
			items_new.push(item);
		});
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
			if (row.cnt > 0) {
				// console.log('already exist!');
				resolve(null);
				return;
			}
			// console.log('1');
			stmt2.run(item_new.url, item_new.img, item_new.name, item_new.price);
			// console.log('2');
			resolve(item_new);
		});
	});
};

var diffItemList = function() {
	setInterval(function() {
		getItemList(function(items_new){
			console.log(new Date());
			db.serialize(function() {
				var stmt1 = db.prepare('SELECT count(*) cnt FROM items where url=?');
				var stmt2 = db.prepare('INSERT INTO items VALUES (?,?,?,?)');
				var promises = [];
				items_new.forEach(function (item_new, index_new, array_new) {
					promises.push(insertData(stmt1, stmt2, item_new));
				});
				Promise.all(promises).then(function(results) {
					stmt1.finalize();
					stmt2.finalize();
					db.get('SELECT count(*) cnt FROM items', function(err, row) {
						console.log('db row count:' + row.cnt);
						var diff = [];
						results.forEach(function (result, index, results) {
							if (result != null) {
								// console.log(result);
								diff.push(result);
							}
						});
						if (diff.length > 0) {
							io.emit('event_diff', diff);
						}
					});
				}).catch(function() {
					console.log('error');
				});
			});
		});
	}, 3000);
};

app.get('/', function(req, res) {
	res.sendFile(__dirname + '/index.html');
});

app.get('/js/:name', function (req, res, next) {
  var options = {
    root: __dirname + '/js/',
    dotfiles: 'deny',
    headers: {
        'x-timestamp': Date.now(),
        'x-sent': true
    }
  };
  var fileName = req.params.name;
  res.sendFile(fileName, options, function (err) {
    if (err) {
      next(err);
    } else {
      console.log('Sent:', fileName);
    }
  });
});

io.on('connection', function(socket) {
	console.log('a user connected');
	socket.on('disconnect', function() {
		console.log('user disconnected');
		// db.close();
	});
	db.serialize(function() {
		var items = [];
		db.each('select * from items', function(err, row) {
			items.push(row);
		}, function() {
			// complete
			console.log(items.length);
			io.emit('event_init', items);
		});
	});
});

http.listen(3000, function() {
	console.log('listening on *:3000');
	db.serialize(function() {
		// create db in memory
		db.run("CREATE TABLE items (url TEXT PRIMARY KEY NOT NULL, img TEXT NOT NULL, name TEXT NOT NULL, price TEXT NOT NULL)");
		// get items first time. init
		getItemList(function(items_new) {
			var stmt1 = db.prepare('SELECT count(*) cnt FROM items where url=?');
			var stmt2 = db.prepare('INSERT INTO items VALUES (?,?,?,?)');
			var promises = [];
			items_new.forEach(function (item_new, index_new, array_new) {
				promises.push(insertData(stmt1, stmt2, item_new));
			});
			Promise.all(promises).then(function(results) {
				// console.log(results.length);
				stmt1.finalize();
				stmt2.finalize();
				db.get('SELECT count(*) cnt FROM items', function(err, row) {
					console.log('db row count:' + row.cnt);
				});
				diffItemList();
			}).catch(function() {
				console.log('error');
			});
		});
	});
});