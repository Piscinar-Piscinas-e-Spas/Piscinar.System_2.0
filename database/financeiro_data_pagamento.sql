ALTER TABLE venda_parcelas
    ADD COLUMN data_pagamento DATE NULL AFTER total_parcelas;

ALTER TABLE servicos_parcelas
    ADD COLUMN data_pagamento DATE NULL AFTER total_parcelas;

ALTER TABLE compra_parcelas
    ADD COLUMN data_pagamento DATE NULL AFTER total_parcelas;
