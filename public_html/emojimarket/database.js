common = document.getElementById('common').innerHTML;
uncommon = document.getElementById('uncommon').innerHTML;
rare = document.getElementById('rare').innerHTML;
epic = document.getElementById('epic').innerHTML;
legendary = document.getElementById('legendary').innerHTML;

function search(){
    input = document.getElementById('input').value;
    console.log(input);

    if(common.includes(input)) {
        document.getElementById('result').innerHTML = 'Common';
    }

    else if(uncommon.includes(input)) {
        document.getElementById('result').innerHTML = 'Uncommon';
    }
    
    else if(rare.includes(input)) {
        document.getElementById('result').innerHTML = 'Rare';
    }
    
    else if(epic.includes(input)) {
        document.getElementById('result').innerHTML = 'Epic';
    }
    
    else if(legendary.includes(input)) {
        document.getElementById('result').innerHTML = 'Legendary';
    }
    

}