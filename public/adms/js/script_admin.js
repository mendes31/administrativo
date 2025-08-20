function confirmDeletion(event, id){

    event.preventDefault();

    Swal.fire({
        title: "Tem certeza que deseja excluir esse registro?",
        text: "Você não poderá reverter esta ação!",
        icon: "warning",
        showCancelButton: true,
        cancelButtonColor: "#0d6efd",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#dc3545",
        confirmButtonText: "Sim, excluir!"
      }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById(`formDelete${id}`).submit();
        }
      });

}

// Apresentar no botão "Processando..." e também o ícone quando o usuário clicar no botão
function showProcessing(buttom){

  // Substituir o texto e o ícone do botão - fa-spin adiciona a animação de rotação
  buttom.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processando...';

  // Desabilitar o botão para evitar que o usuário clicar novamente
  buttom.classList.add('disabled');
}