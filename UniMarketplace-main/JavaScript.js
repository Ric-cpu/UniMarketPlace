const express = require('express');
const multer = require('multer');
const path = require('path');
const bodyParser = require('body-parser');

const app = express();
const PORT = 3000;

// Middleware
app.use(bodyParser.urlencoded({ extended: true }));
app.use(express.static('public'));

// Set up multer for file uploads
const storage = multer.diskStorage({
  destination: 'uploads/',
  filename: (req, file, cb) => {
    cb(null, Date.now() + path.extname(file.originalname));
  },
});

const upload = multer({ storage });

// Handle form submission
app.post('/upload', upload.single('item-image'), (req, res) => {
  const { itemName, itemPrice, itemDescription } = req.body;
  const itemImage = req.file;

  // Process or save the data (e.g., save to database)
  console.log('Item Name:', itemName);
  console.log('Price:', itemPrice);
  console.log('Description:', itemDescription);
  console.log('Image:', itemImage);

  res.send('Item uploaded successfully!');
});

// Start server
app.listen(PORT, () => {
  console.log(`Server running on http://localhost:${PORT}`);
});


 
