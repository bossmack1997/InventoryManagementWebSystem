<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
$username = $_SESSION['username'];
$company  = $_SESSION['company_name'];
$userId   = $_SESSION['user_id']; // user_id from session
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IMWS Dashboard</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
<h2>IMWS</h2>
<div class="welcome-box">
  <p>Welcome,</p>
  <h3 id="usernameDisplay"><?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($company); ?>)</h3>
</div>
<div class="nav">
  <a href="#" class="active">Dashboard</a>
  <a href="#" onclick="logout()">Logout</a>
</div>
<footer>© 2025 IMWS</footer>
</aside>

<!-- MAIN CONTENT -->
<div class="main-content">
<header>
  <button class="menu-toggle" id="menuToggle">☰</button>
  <h1>Inventory Management Web System</h1>
  <button id="addBtn" class="save-btn">Add Item</button>
</header>

<main>
<div class="search-bar">
  <input type="text" id="searchInput" placeholder="Search items..." />
</div>

<table class="inventory-table">
<thead>
<tr>
<th>No.</th>
<th>Item Name</th>
<th>Quantity</th>
<th>Price</th>
<th>Actions</th>
</tr>
</thead>
<tbody id="tableBody"></tbody>
</table>

<p id="itemCount">Total Items: 0</p>

<section id="report-section">
<h2>📊 Inventory Report</h2>
<div style="margin-bottom:8px">
<button id="downloadReportBtn">⬇️ Download Reports</button>
</div>
<div id="report-content"></div>
</section>
</main>
</div>

<!-- MODAL -->
<div class="modal" id="modal">
<div class="modal-content">
  <div class="modal-header">
    <h2>Add Item</h2>
    <span id="closeModal" class="close-btn">&times;</span>
  </div>
  <div class="modal-body">
    <div class="input-group">
      <input type="text" id="itemName" placeholder=" " required />
      <label for="itemName">Item Name</label>
    </div>
    <div class="input-group">
      <input type="number" id="itemQty" placeholder=" " min="1" required />
      <label for="itemQty">Quantity</label>
    </div>
    <div class="input-group">
      <input type="number" id="itemPrice" placeholder=" " min="0" step="0.01" required />
      <label for="itemPrice">Price</label>
    </div>
  </div>
  <div class="modal-footer">
    <button class="cancel-btn" id="closeModalBtn">Cancel</button>
    <button class="save-btn" id="saveItem">Save</button>
  </div>
</div>
</div>
<script>
// Store user_id in JS
const currentUserId = <?php echo json_encode($userId); ?>;

// SIDEBAR TOGGLE
const sidebar = document.getElementById("sidebar");
const menuToggle = document.getElementById("menuToggle");
menuToggle.addEventListener("click", () => sidebar.classList.toggle("active"));
document.addEventListener("click", e => { 
    if(!sidebar.contains(e.target) && !menuToggle.contains(e.target)) sidebar.classList.remove("active"); 
});

// ELEMENTS
const addBtn = document.getElementById("addBtn");
const modal = document.getElementById("modal");
const closeModalBtn = document.getElementById("closeModal");
const saveItemBtn = document.getElementById("saveItem");
const itemName = document.getElementById("itemName");
const itemQty = document.getElementById("itemQty");
const itemPrice = document.getElementById("itemPrice");
const tableBody = document.getElementById("tableBody");
const searchInput = document.getElementById("searchInput");
const itemCount = document.getElementById("itemCount");
const downloadBtn = document.getElementById("downloadReportBtn");

let items = [];

// FETCH ITEMS
async function loadItems() {
    try {
        const res = await fetch("backend/item.php");
        const data = await res.json();

        // Ensure data is always an array
        items = Array.isArray(data) ? data : [];
        renderItems();
    } catch(err){
        console.error(err);
        alert("Failed to load items: " + err.message);
    }
}

// RENDER TABLE
function renderItems() {
    const filtered = items.filter(i => i.item_name.toLowerCase().includes(searchInput.value.toLowerCase()));
    tableBody.innerHTML = "";

    filtered.forEach((item, index) => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${index+1}</td>
            <td>${item.item_name}</td>
            <td>${item.quantity}</td>
            <td>₱${parseFloat(item.price).toLocaleString("en-PH",{minimumFractionDigits:2})}</td>
            <td>
                <button onclick="editItem(${item.id})">✏️</button>
                <button onclick="deleteItem(${item.id})">🗑️</button>
            </td>
        `;
        tableBody.appendChild(row);
    });

    itemCount.textContent = `Total Items: ${filtered.length}`;
    updateReport(filtered);
}

// DYNAMIC REPORT
function updateReport(filteredItems=null) {
    const report = document.getElementById("report-content");
    const reportItems = filteredItems || items;

    if(reportItems.length === 0) {
        report.innerHTML = "<p>No items available.</p>";
        return;
    }

    const totalQty = reportItems.reduce((sum,i)=>sum+Number(i.quantity),0);
    const totalValue = reportItems.reduce((sum,i)=>sum+parseFloat(i.price)*Number(i.quantity),0);
    const lowStock = reportItems.filter(i=>Number(i.quantity)<10);

    report.innerHTML = `
        <p><strong>Total Quantity:</strong> ${totalQty}</p>
        <p><strong>Total Value:</strong> ₱${totalValue.toLocaleString("en-PH",{minimumFractionDigits:2})}</p>
        <p><strong>Low Stock Items:</strong> ${lowStock.length}</p>
    `;
}

// ADD ITEM
saveItemBtn.onclick = async () => {
    const name = itemName.value.trim();
    const qty = parseInt(itemQty.value.trim());
    const price = parseFloat(itemPrice.value.trim());

    if(!name || qty<=0 || isNaN(price)){ 
        alert("Please fill in valid details!"); 
        return; 
    }

    try {
        const res = await fetch("backend/item.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ item_name: name, description: "No description", quantity: qty, price: price })
        });
        const result = await res.json();
        alert(result.message);
        if(result.success){
            modal.style.display = "none";
            itemName.value = itemQty.value = itemPrice.value = "";
            loadItems();
        }
    } catch(err){
        console.error(err);
        alert("Failed to save item: " + err.message);
    }
};

// EDIT ITEM
async function editItem(id){
    const item = items.find(i => i.id==id);
    if(!item) return alert("Item not found.");

    const newName = prompt("Enter new name:", item.item_name);
    const newQty = prompt("Enter new quantity:", item.quantity);
    const newPrice = prompt("Enter new price:", item.price);
    if(!newName || newQty<=0 || newPrice<0) return;

    try{
        const res = await fetch("backend/item.php", {
            method:"PUT",
            headers:{"Content-Type":"application/json"},
            body:JSON.stringify({ id:id, item_name:newName, description:item.description, quantity:newQty, price:newPrice })
        });
        const result = await res.json();
        alert(result.message);
        loadItems();
    }catch(err){ console.error(err); alert("Failed to update item."); }
}

// DELETE ITEM
async function deleteItem(id){
    if(!confirm("Are you sure you want to delete this item?")) return;
    try{
        const res = await fetch("backend/item.php", {
            method:"DELETE",
            headers:{"Content-Type":"application/json"},
            body:JSON.stringify({ id:id })
        });
        const result = await res.json();
        alert(result.message);
        loadItems();
    }catch(err){ console.error(err); alert("Failed to delete item."); }
}

// SEARCH
searchInput.addEventListener("input", renderItems);

// DOWNLOAD REPORT
downloadBtn.addEventListener("click", ()=>{
    if(items.length===0){ alert("No data available!"); return; }

    let csv = "Item Name,Quantity,Price\n";
    items.forEach(i=>{ csv+=`${i.item_name},${i.quantity},${i.price}\n`; });

    const blob = new Blob([csv],{type:"text/csv;charset=utf-8"});
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download="inventory_report.csv";
    link.click();
});

// MODAL CONTROLS
addBtn.onclick = () => modal.style.display="flex";
closeModalBtn.onclick = () => modal.style.display="none";
document.getElementById("closeModalBtn").onclick = () => modal.style.display="none";

// LOGOUT
function logout() {
    if (confirm("Are you sure you want to logout?")) {
        window.location.href = "index.php";
    }
}

// INITIAL LOAD
loadItems();

</script>
</body>
</html>
