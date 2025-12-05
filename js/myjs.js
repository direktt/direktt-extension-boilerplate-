document.addEventListener('DOMContentLoaded', function() {
    // Create div
    const div = document.createElement('div');
    
    // Create H1
    const h1 = document.createElement('h1');
    h1.textContent = 'HI!!! I am here';
    
    // Append H1 to div
    div.appendChild(h1);
    
    // Append div to body
    document.body.appendChild(div);
});