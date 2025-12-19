using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace BrasilBurger.Models
{
    [Table("zone")]
    public class Zone
    {
        [Key]
        [Column("id")]
        public int Id { get; set; }

        [Column("quartier")]
        public string Quartier { get; set; } = "";

        [Column("prix")]
        public decimal Prix { get; set; }

        [Column("est_archive")]
        public bool EstArchive { get; set; } = false;
    }
}