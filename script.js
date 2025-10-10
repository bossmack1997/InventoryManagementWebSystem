  const addBtn = document.getElementById("addBtn");
    const modal = document.getElementById("modal");
    const closeModal = document.getElementById("closeModal");
    const saveItem = document.getElementById("saveItem");
    const itemName = document.getElementById("itemName");
    const itemQty = document.getElementById("itemQty");
    const tableBody = document.getElementById("tableBody");
    const searchInput = document.getElementById("searchInput");
    const itemCount = document.getElementById("itemCount");

    let items = JSON.parse(localStorage.getItem("inventoryItems")) || [];

    function renderItems() {
      tableBody.innerHTML = "";
      const filtered = items.filter(i =>
        i.name.toLowerCase().includes(searchInput.value.toLowerCase())
      );

      filtered.forEach((item, index) => {
        const row = document.createElement("tr");
        row.innerHTML = `
          <td>${index + 1}</td>
          <td>${item.name}</td>
          <td>${item.quantity}</td>
          <td class="actions">
            <button onclick="editItem(${index})">✏️</button>
            <button onclick="deleteItem(${index})">🗑️</button>
          </td>
        `;
        tableBody.appendChild(row);
      });

      itemCount.textContent = "Total: " + filtered.length;
    }

    function saveToLocalStorage() {
      localStorage.setItem("inventoryItems", JSON.stringify(items));
    }

    addBtn.onclick = () => {
      modal.style.display = "flex";
      itemName.value = "";
      itemQty.value = "";
    };

    closeModal.onclick = () => {
      modal.style.display = "none";
    };

    saveItem.onclick = () => {
      const name = itemName.value.trim();
      const qty = parseInt(itemQty.value.trim());
      if (name && qty > 0) {
        items.push({ name, quantity: qty });
        saveToLocalStorage();
        renderItems();
        modal.style.display = "none";
      } else {
        alert("Please enter valid item name and quantity!");
      }
    };

    function deleteItem(index) {
      if (confirm("Delete this item?")) {
        items.splice(index, 1);
        saveToLocalStorage();
        renderItems();
      }
    }

    function editItem(index) {
      const newName = prompt("Enter new name:", items[index].name);
      const newQty = prompt("Enter new quantity:", items[index].quantity);
      if (newName && newQty > 0) {
        items[index].name = newName;
        items[index].quantity = parseInt(newQty);
        saveToLocalStorage();
        renderItems();
      }
    }

    searchInput.addEventListener("input", renderItems);
    window.onclick = (e) => { if (e.target == modal) modal.style.display = "none"; };
    renderItems();