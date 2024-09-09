import pysam
import sys
samfile = pysam.AlignmentFile(sys.argv[1], "rb")
i=0
for read in samfile.fetch():
     i=i+1
     cig=read.cigarstring #for cigarstring
     aligned=read.get_aligned_pairs(with_seq=True) # will give you the aligned pairs
#     with open("output_" + str(i) + ".txt", 'w') as file_handler:
#          for item in aligned:
#              file_handler.write("{}\n".format(item))
     for item in aligned:
         sys.stdout.write("{}\n".format(item))
