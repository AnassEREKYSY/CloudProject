   
  function replaceIcons() {
    const iconMap = {
      '<<': 'fas fa-angle-double-left',
      '<': 'fas fa-angle-left',
      '>': 'fas fa-angle-right',
      '>>': 'fas fa-angle-double-right'
    };

    const paginationElements = document.querySelectorAll('.pagination span a');
    console.log(paginationElements);
    paginationElements.forEach(element => {
      const textContent = element.textContent.trim();
      console.log(textContent);
      if (iconMap[textContent]) {
        const iconElement = document.createElement('i');
        iconElement.className = iconMap[textContent];
        element.innerHTML = '';
        element.appendChild(iconElement);
      }
    });
  }

  replaceIcons();