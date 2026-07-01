<style>
    @media (min-width: 992px) {
        .modal-xxl {
            max-width: 96%;
        }

        .modal-xxl.modal-dialog-scrollable {
            height: calc(100% - 2rem);
        }
    }

    a.popup-hutang {
        text-decoration: none;
        cursor: pointer;
    }

    a.popup-hutang:hover {
        text-decoration: underline;
    }

    #detailHutangModalBack {
        flex-shrink: 0;
    }

    #detailHutangModal .modal-header .btn-close {
        margin: 0;
    }

    #detailHutangModal .card > .card-header:has(.card-title) {
        display: none;
    }
</style>
